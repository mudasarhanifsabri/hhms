<?php

use App\Models\LandlordAccountEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CUTOFF = '2026-09-01';

    public function up(): void
    {
        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($host !== 'rms.dt-server.com') {
            return;
        }

        // An extension/renewal is its own stay period. Keep the parent booking as the
        // required container when one of those periods starts on or after the cutoff.
        $retainedPeriodBookingIds = DB::table('booking_invoices')
            ->whereIn('invoice_type', ['extension', 'renewal'])
            ->whereDate('period_from', '>=', self::CUTOFF)
            ->pluck('booking_id')
            ->unique();
        $bookingIds = DB::table('bookings')->whereDate('check_in', '<', self::CUTOFF)
            ->whereNotIn('id', $retainedPeriodBookingIds)
            ->pluck('id');
        $expenseRows = DB::table('expenses')->whereDate('expense_date', '<', self::CUTOFF)->get(['id', 'expense_no', 'landlord_id']);
        $expenseIds = $expenseRows->pluck('id');
        $staleRetainedInvoiceIds = DB::table('booking_invoices')
            ->whereIn('booking_id', $retainedPeriodBookingIds)
            ->where(function ($query) {
                $query->where('invoice_type', 'original')
                    ->orWhereNull('period_from')
                    ->orWhereDate('period_from', '<', self::CUTOFF);
            })->pluck('id');
        $invoiceIds = DB::table('booking_invoices')->whereIn('booking_id', $bookingIds)->pluck('id')
            ->merge($staleRetainedInvoiceIds)->unique()->values();
        $invoiceNumbers = DB::table('booking_invoices')->whereIn('id', $invoiceIds)->pluck('invoice_number');
        $paymentIds = DB::table('booking_invoice_payments')->whereIn('booking_invoice_id', $invoiceIds)->pluck('id');
        $bookingReferences = DB::table('bookings')->whereIn('id', $bookingIds)->pluck('booking_reference');
        $taskIds = DB::table('booking_tasks')->whereIn('booking_id', $bookingIds)->pluck('id');
        $inspectionIds = DB::table('booking_inspections')->whereIn('booking_id', $bookingIds)
            ->orWhereIn('booking_task_id', $taskIds)->pluck('id');
        $affectedPropertyIds = DB::table('bookings')->whereIn('id', $bookingIds)->pluck('property_id')->filter()->unique();
        $affectedLandlordIds = $expenseRows->pluck('landlord_id')->filter()
            ->merge(DB::table('landlord_account_entries')->where(function ($query) use ($invoiceIds, $invoiceNumbers, $paymentIds, $bookingReferences) {
                $query->whereIn('booking_invoice_id', $invoiceIds)
                    ->orWhereIn('reference', $bookingReferences)
                    ->orWhereIn('reference', $invoiceNumbers)
                    ->orWhereIn('reference', $paymentIds->map(fn ($id) => 'PAY-'.$id))
                    ->orWhereIn('reference', $paymentIds->map(fn ($id) => 'REV-'.$id));
            })->pluck('landlord_id'))
            ->unique();

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($bookingIds, $retainedPeriodBookingIds, $expenseRows, $expenseIds, $invoiceIds, $invoiceNumbers, $paymentIds, $bookingReferences, $taskIds, $inspectionIds) {
                if ($expenseIds->isNotEmpty()) {
                    DB::table('expense_audits')->whereIn('expense_id', $expenseIds)->delete();
                    DB::table('accounting_entries')->whereIn('expense_id', $expenseIds)->delete();
                    DB::table('utility_bills')->whereIn('expense_id', $expenseIds)->update(['expense_id' => null, 'accounting_entry_id' => null]);
                    DB::table('landlord_account_entries')->whereIn('reference', $expenseRows->pluck('expense_no'))
                        ->orWhere(function ($query) use ($expenseRows) {
                            foreach ($expenseRows->pluck('expense_no')->filter() as $expenseNo) {
                                $query->orWhere('reference', 'like', 'DRAFT-REV-'.$expenseNo.'-%');
                            }
                        })->delete();
                    DB::table('expenses')->whereIn('id', $expenseIds)->delete();
                }

                if ($bookingIds->isEmpty() && $invoiceIds->isEmpty()) {
                    return;
                }

                $paymentAccountingEntryIds = DB::table('booking_invoice_payments')
                    ->whereIn('id', $paymentIds)->pluck('accounting_entry_id')->filter();
                $depositAccountingEntryIds = DB::table('booking_deposit_entries')
                    ->whereIn('booking_invoice_id', $invoiceIds)->pluck('accounting_entry_id')->filter();
                DB::table('accounting_entries')->whereIn('booking_id', $bookingIds)
                    ->orWhereIn('id', $paymentAccountingEntryIds)
                    ->orWhereIn('id', $depositAccountingEntryIds)
                    ->delete();
                DB::table('landlord_account_entries')->whereIn('booking_invoice_id', $invoiceIds)
                    ->orWhereIn('reference', $bookingReferences)
                    ->orWhereIn('reference', $invoiceNumbers)
                    ->orWhereIn('reference', $paymentIds->map(fn ($id) => 'PAY-'.$id))
                    ->orWhereIn('reference', $paymentIds->map(fn ($id) => 'REV-'.$id))
                    ->delete();

                if (Schema::hasTable('inspection_upload_tokens')) {
                    DB::table('inspection_upload_tokens')->whereIn('inspection_id', $inspectionIds)->delete();
                }
                if (Schema::hasTable('unit_inventory_reviews')) {
                    DB::table('unit_inventory_reviews')->whereIn('inspection_id', $inspectionIds)->delete();
                }
                if (Schema::hasTable('unit_inventory_movements')) {
                    DB::table('unit_inventory_movements')->whereIn('inspection_id', $inspectionIds)->delete();
                }
                DB::table('booking_inspection_items')->whereIn('booking_inspection_id', $inspectionIds)->delete();
                DB::table('booking_inspections')->whereIn('id', $inspectionIds)->delete();
                DB::table('booking_task_remarks')->whereIn('booking_task_id', $taskIds)->delete();
                DB::table('booking_task_activities')->whereIn('booking_task_id', $taskIds)->delete();
                DB::table('booking_task_cost_items')->whereIn('booking_task_id', $taskIds)->delete();
                DB::table('expenses')->whereIn('booking_task_id', $taskIds)->update(['booking_task_id' => null]);
                DB::table('booking_tasks')->whereIn('id', $taskIds)->delete();
                DB::table('booking_deposit_entries')->whereIn('booking_id', $bookingIds)
                    ->orWhereIn('booking_invoice_id', $invoiceIds)
                    ->orWhereIn('booking_invoice_payment_id', $paymentIds)
                    ->delete();
                $refunds = DB::table('booking_deposit_refunds')->whereIn('booking_id', $bookingIds);
                if (Schema::hasColumn('booking_deposit_refunds', 'related_booking_id')) {
                    $refunds->orWhereIn('related_booking_id', $bookingIds);
                }
                $refunds->delete();
                DB::table('booking_invoice_payments')->whereIn('booking_invoice_id', $invoiceIds)->delete();
                if (Schema::hasTable('booking_payment_batches')) {
                    DB::table('booking_payment_batches')->whereIn('booking_id', $bookingIds)->delete();
                }
                DB::table('booking_invoices')->whereIn('id', $invoiceIds)->delete();
                DB::table('booking_histories')->whereIn('booking_id', $retainedPeriodBookingIds)
                    ->whereDate('created_at', '<', self::CUTOFF)->delete();
                DB::table('booking_histories')->whereIn('booking_id', $bookingIds)->delete();
                DB::table('bookings')->whereIn('id', $bookingIds)->delete();
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $affectedLandlordIds->each(fn (string $id) => LandlordAccountEntry::recalculateBalancesFor($id));
        DB::table('bank_accounts')->get(['id', 'opening_balance'])->each(function ($account) {
            $movement = (float) DB::table('accounting_entries')->where('paid_from_account_id', $account->id)
                ->whereIn('approval_status', ['posted', 'approved', 'paid'])->selectRaw('COALESCE(SUM(credit-debit),0) total')->value('total');
            DB::table('bank_accounts')->where('id', $account->id)->update(['current_balance' => (float) $account->opening_balance + $movement]);
        });
        $affectedPropertyIds->each(function (string $propertyId) {
            $hasCurrentBooking = DB::table('bookings')->where('property_id', $propertyId)
                ->whereDate('check_in', '<=', today())->whereDate('check_out', '>=', today())
                ->where('status', '!=', 'checked_out')->exists();
            if (! $hasCurrentBooking) {
                DB::table('properties')->where('id', $propertyId)->whereIn('status', ['booked', 'rented'])->update(['status' => 'available']);
            }
        });
    }

    public function down(): void
    {
        // RMS cleanup is intentionally irreversible; HHMS remains the historical source.
    }
};

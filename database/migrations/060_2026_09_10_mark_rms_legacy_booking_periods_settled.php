<?php

use App\Models\LandlordAccountEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('booking_invoices', 'legacy_owner_settled')) {
            Schema::table('booking_invoices', function (Blueprint $table) {
                $table->boolean('legacy_owner_settled')->default(false)->index()->after('status');
            });
        }

        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($host !== 'rms.dt-server.com') {
            return;
        }

        $invoiceIds = DB::table('booking_invoices')
            ->join('bookings', 'bookings.id', '=', 'booking_invoices.booking_id')
            ->whereDate('bookings.check_in', '<', '2026-09-01')
            ->where(function ($query) {
                $query->whereDate('booking_invoices.period_from', '<', '2026-09-01')
                    ->orWhere(function ($missingPeriod) {
                        $missingPeriod->whereNull('booking_invoices.period_from')
                            ->whereDate('booking_invoices.issue_date', '<', '2026-09-01');
                    });
            })->pluck('booking_invoices.id');

        if ($invoiceIds->isEmpty()) {
            return;
        }

        $invoiceNumbers = DB::table('booking_invoices')->whereIn('id', $invoiceIds)->pluck('invoice_number');
        $payments = DB::table('booking_invoice_payments')->whereIn('booking_invoice_id', $invoiceIds)->get(['id', 'accounting_entry_id']);
        $paymentReferences = $payments->pluck('id')->map(fn ($id) => 'PAY-'.$id);
        $landlordIds = DB::table('landlord_account_entries')->whereIn('booking_invoice_id', $invoiceIds)
            ->orWhereIn('reference', $invoiceNumbers)->orWhereIn('reference', $paymentReferences)
            ->pluck('landlord_id')->filter()->unique();

        DB::transaction(function () use ($invoiceIds, $invoiceNumbers, $payments, $paymentReferences) {
            DB::table('landlord_account_entries')->whereIn('booking_invoice_id', $invoiceIds)
                ->orWhereIn('reference', $invoiceNumbers)->orWhereIn('reference', $paymentReferences)->delete();
            DB::table('accounting_entries')->whereIn('id', $payments->pluck('accounting_entry_id')->filter())->delete();
            DB::table('booking_invoice_payments')->whereIn('id', $payments->pluck('id'))
                ->update(['accounting_entry_id' => null]);
            DB::table('booking_invoices')->whereIn('id', $invoiceIds)
                ->update(['legacy_owner_settled' => true, 'status' => 'paid', 'updated_at' => now()]);
        });

        $landlordIds->each(fn (string $id) => LandlordAccountEntry::recalculateBalancesFor($id));
        DB::table('bank_accounts')->get(['id', 'opening_balance'])->each(function ($account) {
            $movement = (float) DB::table('accounting_entries')->where('paid_from_account_id', $account->id)
                ->whereIn('approval_status', ['posted', 'approved', 'paid'])
                ->selectRaw('COALESCE(SUM(credit-debit),0) total')->value('total');
            DB::table('bank_accounts')->where('id', $account->id)
                ->update(['current_balance' => (float) $account->opening_balance + $movement]);
        });
    }

    public function down(): void
    {
        Schema::table('booking_invoices', fn (Blueprint $table) => $table->dropColumn('legacy_owner_settled'));
    }
};

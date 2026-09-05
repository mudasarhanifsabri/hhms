<?php

namespace App\Support;

use App\Models\AccountingEntry;
use App\Models\Booking;
use App\Models\BookingDepositEntry;
use App\Models\BookingInvoicePayment;
use App\Models\LandlordAccountEntry;
use Illuminate\Support\Facades\DB;

class InvoiceAllocationRebuilder
{
    public static function rebuild(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $payments = BookingInvoicePayment::whereHas('invoice', fn ($query) => $query->where('booking_id', $booking->id))
                ->whereNull('reversed_at')->orderBy('payment_date')->orderBy('created_at')->lockForUpdate()->get();
            if ($payments->isEmpty() || $payments->contains(fn ($payment) => ! $payment->allocation)) {
                return false;
            }
            if (BookingInvoicePayment::whereHas('invoice', fn ($query) => $query->where('booking_id', $booking->id))->whereNotNull('reversed_at')->exists()
                || BookingDepositEntry::where('booking_id', $booking->id)->where('kind', '!=', 'received')->exists()) {
                return false;
            }

            $depositEntries = BookingDepositEntry::where('booking_id', $booking->id)->where('kind', 'received')->lockForUpdate()->get();
            foreach ($depositEntries as $deposit) {
                $payment = $payments->firstWhere('id', $deposit->booking_invoice_payment_id);
                if ($payment?->accounting_entry_id) {
                    $source = AccountingEntry::whereKey($payment->accounting_entry_id)->lockForUpdate()->first();
                    $source?->update([
                        'credit' => round((float) $source->credit + (float) $deposit->amount, 2),
                        'gross_amount' => round((float) $source->gross_amount + (float) $deposit->amount, 2),
                        'net_amount' => round((float) $source->net_amount + (float) $deposit->amount, 2),
                    ]);
                }
                AccountingEntry::whereKey($deposit->accounting_entry_id)->delete();
                $deposit->delete();
            }

            AccountingEntry::whereIn('id', $payments->flatMap(fn ($payment) => $payment->allocation_entry_ids ?? [])->filter()->all())->delete();
            LandlordAccountEntry::whereIn('reference', $payments->map(fn ($payment) => 'PAY-'.$payment->id))->delete();
            $payments->each->update(['allocation' => null, 'allocation_entry_ids' => null, 'rent_amount' => null]);

            foreach ($booking->invoices()->orderBy('issue_date')->orderBy('created_at')->get() as $invoice) {
                $remaining = [
                    'rent' => (float) $invoice->rent_amount, 'vat' => (float) $invoice->vat_amount,
                    'deposit' => (float) (($invoice->fees ?? [])['Security Deposit'] ?? 0),
                    'cleaning' => (float) (($invoice->fees ?? [])['Cleaning Fee'] ?? 0),
                    'agency' => (float) (($invoice->fees ?? [])['Agency Fee'] ?? 0),
                    'tourism' => (float) (($invoice->fees ?? [])['DTCM Fee'] ?? 0),
                    'other' => (float) collect($invoice->fees ?? [])->except(['Security Deposit', 'Cleaning Fee', 'Agency Fee', 'DTCM Fee'])->sum(),
                ];
                foreach ($payments->where('booking_invoice_id', $invoice->id) as $payment) {
                    $allocation = InvoiceSettlement::allocateAmounts($remaining, (float) $payment->amount);
                    foreach ($remaining as $key => $amount) {
                        $remaining[$key] = round($amount - $allocation[$key], 2);
                    }
                    $payment->update([
                        'allocation' => $allocation,
                        'rent_amount' => $booking->owner_posting_basis === 'receipts' ? $allocation['rent'] : null,
                    ]);
                    if ($allocation['deposit'] > 0) {
                        DepositWallet::allocate($booking, $payment, $allocation['deposit'], 'rebuild:'.$payment->id);
                    }
                    OwnerReceiptPosting::post($payment->fresh('invoice.booking.property'));
                    InvoiceSettlement::post($payment->fresh('invoice.booking.property'));
                }
            }

            foreach ($payments->groupBy('accounting_entry_id') as $sourceId => $sourcePayments) {
                AccountingEntry::whereKey($sourceId)->update(['vat_amount' => round($sourcePayments->sum(fn ($payment) => (float) ($payment->fresh()->allocation['vat'] ?? 0)), 2)]);
            }
            if ($booking->property?->landlord_id) {
                LandlordAccountEntry::recalculateBalancesFor($booking->property->landlord_id);
            }
            $booking->histories()->create(['title' => 'Payment Allocations Rebuilt', 'description' => 'Existing automatic allocations changed to deposit-first allocation. Bank receipt totals were unchanged.']);

            return true;
        });
    }
}

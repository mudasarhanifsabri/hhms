<?php

namespace App\Support;

use App\Models\AccountingEntry;
use App\Models\Booking;
use App\Models\BookingDepositEntry;
use App\Models\BookingDepositRefund;
use App\Models\LandlordAccountEntry;
use Illuminate\Support\Facades\DB;

class LegacyOwnerReconciliation
{
    public static function inspect(Booking $booking): array
    {
        if ($booking->owner_posting_basis === 'receipts') {
            return ['eligible' => false, 'reason' => 'Already payment-based.'];
        }
        $invoices = $booking->invoices()->with('allPayments')->get();
        if ($invoices->isEmpty()) {
            return ['eligible' => false, 'reason' => 'No saved invoices; review the original booking first.'];
        }
        if ($booking->invoice_status === 'paid' || $booking->payment_proof || $invoices->contains(fn ($i) => $i->status !== 'unpaid' || $i->allPayments->isNotEmpty())) {
            return ['eligible' => false, 'reason' => 'Payment records, proof or a paid/partial status exist. Verify rent allocations before conversion.'];
        }
        if (AccountingEntry::where('booking_id', $booking->id)->where(function ($q) {
            $q->where('type', 'income')->orWhereNotNull('paid_from_account_id');
        })->exists() || BookingDepositEntry::where('booking_id', $booking->id)->exists() || BookingDepositRefund::where('booking_id', $booking->id)->exists()) {
            return ['eligible' => false, 'reason' => 'Ledger or deposit activity exists despite invoice status. Review before conversion.'];
        }
        $references = $invoices->pluck('invoice_number')->push($booking->booking_reference)->filter()->unique();
        $entries = LandlordAccountEntry::whereIn('reference', $references)->whereIn('type', ['rent_income', 'management_fee'])->get();
        if ($entries->groupBy(fn ($entry) => $entry->reference.'|'.$entry->type)->contains(fn ($rows) => $rows->count() > 1)) {
            return ['eligible' => false, 'reason' => 'Duplicate owner posting references exist; review before conversion.'];
        }
        foreach ($entries as $entry) {
            $description = $entry->description ?? '';
            $automatic = $entry->type === 'rent_income'
                ? str_starts_with($description, 'Booking rent income for ') || preg_match('/^(Original Booking|Extension|Renewal) rent for /', $description)
                : str_starts_with($description, 'Management fee ');
            if (! $automatic || $entry->property_id !== $booking->property_id || $entry->landlord_id !== $booking->property?->landlord_id
                || $entry->direction !== ($entry->type === 'rent_income' ? 'credit' : 'debit')) {
                return ['eligible' => false, 'reason' => 'Owner entries have manual, ownership or reference differences; review before conversion.'];
            }
        }

        return ['eligible' => true, 'reason' => 'Unpaid with no recorded payment, proof, ledger receipt or deposit activity.', 'entries' => $entries];
    }

    public static function reconcile(Booking $booking): array
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $check = self::inspect($booking);
            if (! $check['eligible']) {
                return $check;
            }
            foreach ($check['entries'] as $entry) {
                LandlordAccountEntry::firstOrCreate(['reference' => 'RECON-'.$entry->id], [
                    'landlord_id' => $entry->landlord_id, 'property_id' => $entry->property_id,
                    'entry_date' => $entry->entry_date,
                    'direction' => $entry->direction === 'credit' ? 'debit' : 'credit',
                    'type' => $entry->direction === 'credit' ? 'adjustment_debit' : 'adjustment_credit',
                    'amount' => $entry->amount,
                    'description' => 'Unpaid booking reconciliation: reversal of automatic '.$entry->type_label.' / '.$entry->reference.'. Original entry '.$entry->id.' preserved; no bank or deposit movement.',
                ]);
            }
            $booking->update(['owner_posting_basis' => 'receipts']);
            foreach ($check['entries']->pluck('landlord_id')->unique() as $id) {
                LandlordAccountEntry::recalculateBalancesFor($id);
            }
            $booking->histories()->create(['title' => 'Owner Posting Reconciled', 'description' => $check['entries']->count().' automatic owner entries offset on their original dates. Original records preserved. Unpaid rent is now expected only; future owner income requires actual rent receipts. Actor: '.(auth()->id() ?? 'System deployment/console').'.']);

            return ['eligible' => true, 'reason' => 'Converted to payment-based posting; automatic uncollected rent and management fees offset.', 'reversed' => $check['entries']->count()];
        });
    }
}

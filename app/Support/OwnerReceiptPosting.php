<?php

namespace App\Support;

use App\Models\BookingInvoicePayment;
use App\Models\LandlordAccountEntry;

class OwnerReceiptPosting
{
    public static function post(BookingInvoicePayment $payment): void
    {
        $booking = $payment->invoice->booking;
        if ($booking->owner_posting_basis !== 'receipts' || ! $booking->property?->landlord_id || (float) $payment->rent_amount <= 0) {
            return;
        }
        $base = ['landlord_id' => $booking->property->landlord_id, 'property_id' => $booking->property_id,
            'entry_date' => $payment->payment_date, 'reference' => 'PAY-'.$payment->id];
        $description = $payment->invoice->invoice_number.' — collected rent from '.$booking->guest_name;
        LandlordAccountEntry::firstOrCreate(['reference' => $base['reference'], 'type' => 'rent_income'], $base + [
            'type' => 'rent_income', 'direction' => 'credit', 'amount' => $payment->rent_amount, 'description' => $description]);
        $fee = round((float) $payment->rent_amount * (float) $booking->management_fee_percent / 100, 2);
        if ($fee > 0) {
            LandlordAccountEntry::firstOrCreate(['reference' => $base['reference'], 'type' => 'management_fee'], $base + [
                'type' => 'management_fee', 'direction' => 'debit', 'amount' => $fee,
                'description' => 'Management fee '.$booking->management_fee_percent.'% — '.$description.' (no management-fee VAT)']);
        }
        LandlordAccountEntry::recalculateBalancesFor($base['landlord_id']);
    }

    public static function reverse(BookingInvoicePayment $payment, string $reason): void
    {
        $entries = LandlordAccountEntry::where('reference', 'PAY-'.$payment->id)->get();
        foreach ($entries as $entry) {
            LandlordAccountEntry::create(['landlord_id' => $entry->landlord_id, 'property_id' => $entry->property_id,
                'entry_date' => today(), 'reference' => 'REV-'.$payment->id,
                'type' => $entry->direction === 'credit' ? 'adjustment_debit' : 'adjustment_credit',
                'direction' => $entry->direction === 'credit' ? 'debit' : 'credit', 'amount' => $entry->amount,
                'description' => 'Reversal of '.$entry->type_label.' for '.$payment->invoice->invoice_number.': '.$reason]);
        }
        foreach ($entries->pluck('landlord_id')->unique() as $id) {
            LandlordAccountEntry::recalculateBalancesFor($id);
        }
    }
}

<?php

namespace App\Support;

use App\Models\AccountingAccount;
use App\Models\AccountingEntry;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceSettlement
{
    public static function allocation(BookingInvoice $invoice, ?float $paymentAmount = null): array
    {
        $parts = ['rent' => (float) $invoice->rent_amount, 'vat' => (float) $invoice->vat_amount,
            'deposit' => (float) (($invoice->fees ?? [])['Security Deposit'] ?? 0),
            'cleaning' => (float) (($invoice->fees ?? [])['Cleaning Fee'] ?? 0),
            'agency' => (float) (($invoice->fees ?? [])['Agency Fee'] ?? 0),
            'tourism' => (float) (($invoice->fees ?? [])['DTCM Fee'] ?? 0),
            'other' => (float) collect($invoice->fees ?? [])->except(['Security Deposit', 'Cleaning Fee', 'Agency Fee', 'DTCM Fee'])->sum()];
        if (DepositWallet::cents(array_sum($parts)) !== DepositWallet::cents($invoice->total_amount) || min($parts) < 0) {
            throw ValidationException::withMessages(['amount' => 'Invoice charges do not reconcile to its total. Correct the invoice before receiving payment.']);
        }
        foreach ($invoice->payments as $payment) {
            if (! $payment->allocation) {
                throw ValidationException::withMessages(['amount' => 'This invoice contains an older payment without a complete allocation. Reconcile that payment before automatic settlement; its history has not been changed.']);
            }
            foreach ($parts as $key => $value) {
                $parts[$key] = round($value - (float) ($payment->allocation[$key] ?? 0), 2);
            }
        }
        if (min($parts) < 0) {
            throw ValidationException::withMessages(['amount' => 'Existing allocations exceed invoice charges. Reconciliation is required.']);
        }

        $paymentAmount ??= array_sum($parts);

        return self::allocateAmounts($parts, $paymentAmount);
    }

    public static function allocateAmounts(array $parts, float $paymentAmount): array
    {
        $remainingTotal = round(array_sum($parts), 2);
        if (DepositWallet::cents($paymentAmount) <= 0 || DepositWallet::cents($paymentAmount) > DepositWallet::cents($remainingTotal)) {
            throw ValidationException::withMessages(['amount' => 'Payment must be greater than zero and cannot exceed the outstanding invoice balance.']);
        }

        // Guest deposits are normally collected first. Once held, subsequent receipts settle
        // rent and then the remaining invoice charges in a predictable waterfall.
        $allocated = array_fill_keys(array_keys($parts), 0.0);
        $left = round($paymentAmount, 2);
        foreach (['deposit', 'rent', 'vat', 'tourism', 'cleaning', 'agency', 'other'] as $key) {
            if ($left <= 0) {
                break;
            }
            $amount = min((float) $parts[$key], $left);
            $allocated[$key] = $amount;
            $left = round($left - $amount, 2);
        }

        return $allocated;
    }

    public static function assertPaid(BookingInvoice $invoice): void
    {
        abort_unless($invoice->status === 'paid' && DepositWallet::cents($invoice->payments()->sum('amount')) >= DepositWallet::cents($invoice->total_amount), 422,
            'Booking confirmation is available only after the full invoice amount has been recorded as paid.');
    }

    public static function assertBookingPaid(Booking $booking): void
    {
        $invoices = $booking->invoices()->get();
        abort_if($invoices->isEmpty(), 422, 'A fully paid invoice is required before generating a booking confirmation.');
        foreach ($invoices as $invoice) {
            self::assertPaid($invoice);
        }
    }

    public static function post(BookingInvoicePayment $payment): void
    {
        $booking = $payment->invoice->booking;
        $parts = $payment->allocation;
        $rate = (float) ($booking->agent_commission_percent ?? $booking->agent?->agent_commission ?? 0);
        if ($rate < 0 || $rate > 100 || (float) $booking->management_fee_percent < 0 || (float) $booking->management_fee_percent > 100) {
            throw ValidationException::withMessages(['amount' => 'Commission percentages must be between 0 and 100.']);
        }
        $commission = $booking->agent_id ? round($parts['agency'] * $rate / 100, 2) : 0;
        $management = round($parts['rent'] * (float) $booking->management_fee_percent / 100, 2);
        $booking->forceFill(['agent_commission_percent' => $booking->agent_id ? $rate : 0])->save();
        $mapping = [
            ['2020', 'Owner rent payable', $parts['rent'] - $management],
            ['4100', 'Management fee income', $management],
            ['4020', 'Cleaning fee income', $parts['cleaning']],
            ['4110', 'Agency fee company share', $parts['agency'] - $commission],
            ['2097', 'Agent commission payable: '.($booking->agent?->name ?? 'No agent'), $commission],
            ['2040', 'VAT payable', $parts['vat']],
            ['2098', 'Tourism fees payable', $parts['tourism']],
            ['4990', 'Other invoice fees', $parts['other']],
        ];
        $base = ['entry_date' => $payment->payment_date, 'type' => 'adjustment', 'category' => 'invoice_allocation',
            'booking_id' => $booking->id, 'property_id' => $booking->property_id, 'landlord_id' => $booking->property?->landlord_id,
            'paid_from_account_id' => null, 'status' => 'posted', 'approval_status' => 'posted', 'created_by' => auth()->id(),
            'transaction_reference' => $payment->reference ?: $payment->invoice->invoice_number, 'debit' => 0, 'credit' => 0, 'vat_amount' => 0, 'vat_rate' => 0];
        $ids = [];
        $nonDeposit = round((float) $payment->amount - $parts['deposit'], 2);
        foreach ([['2096', 'Allocate receipt clearing', -$nonDeposit], ...$mapping] as [$code, $label, $amount]) {
            if (abs($amount) < .005) {
                continue;
            }
            $account = AccountingAccount::where('code', $code)->where('is_active', true)->firstOrFail();
            $entry = AccountingEntry::create(array_merge($base, ['entry_no' => 'ALLOC-'.Str::upper(Str::random(16)), 'accounting_account_id' => $account->id,
                'description' => $label.' — '.$payment->invoice->invoice_number.' / '.$payment->id,
                'debit' => $amount < 0 ? -$amount : 0, 'credit' => $amount > 0 ? $amount : 0, 'net_amount' => $amount, 'gross_amount' => $amount]));
            $ids[] = $entry->id;
        }
        $parts += ['agent_id' => $booking->agent_id, 'agent_commission_percent' => $rate, 'agent_commission' => $commission, 'agency_company_share' => $parts['agency'] - $commission, 'management_fee' => $management];
        $payment->update(['allocation' => $parts, 'allocation_entry_ids' => $ids]);
    }

    public static function reverse(BookingInvoicePayment $payment, string $reason): void
    {
        foreach (AccountingEntry::whereIn('id', $payment->allocation_entry_ids ?? [])->lockForUpdate()->get() as $source) {
            $entry = $source->replicate();
            $entry->fill(['entry_no' => 'REV-'.Str::upper(Str::random(16)), 'entry_date' => today(), 'debit' => $source->credit, 'credit' => $source->debit,
                'net_amount' => -(float) $source->net_amount, 'gross_amount' => -(float) $source->gross_amount,
                'description' => 'Allocation reversal '.$source->entry_no.': '.$reason, 'created_by' => auth()->id()])->save();
        }
    }
}

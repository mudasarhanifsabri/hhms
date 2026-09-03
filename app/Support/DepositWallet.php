<?php

namespace App\Support;

use App\Models\AccountingAccount;
use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\BookingDepositEntry;
use App\Models\BookingDepositRefund;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DepositWallet
{
    public static function cents($amount): int
    {
        return (int) round((float) $amount * 100);
    }

    public static function totals(Booking $booking): array
    {
        $groups = BookingDepositEntry::where('booking_id', $booking->id)->selectRaw('kind, SUM(amount) AS total')->groupBy('kind')->pluck('total', 'kind');
        $totals = [];
        foreach (['received', 'deducted', 'refunded', 'carry_in', 'carry_out'] as $kind) {
            $totals[$kind] = (float) ($groups[$kind] ?? 0);
        }
        $totals['held'] = round($totals['received'] + $totals['carry_in'] - $totals['deducted'] - $totals['refunded'] - $totals['carry_out'], 2);

        return $totals;
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['deposit' => $message]);
    }

    private static function ledger(Booking $booking, string $account, array $data): AccountingEntry
    {
        return AccountingEntry::create(array_merge([
            'entry_no' => 'DEP-'.Str::upper(Str::random(16)), 'entry_date' => today(),
            'type' => 'deposit', 'category' => 'security_deposit',
            'accounting_account_id' => AccountingAccount::where('code', $account)->firstOrFail()->id,
            'booking_id' => $booking->id, 'property_id' => $booking->property_id,
            'debit' => 0, 'credit' => 0, 'vat_rate' => 0, 'vat_amount' => 0,
            'net_amount' => 0, 'gross_amount' => 0, 'status' => 'posted', 'approval_status' => 'posted', 'created_by' => auth()->id(),
        ], $data));
    }

    // Reclassifies part of an existing receipt. Never collects money a second time.
    public static function allocate(Booking $booking, BookingInvoicePayment $payment, float $amount, string $submission): BookingDepositEntry
    {
        return DB::transaction(function () use ($booking, $payment, $amount, $submission) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $invoice = BookingInvoice::whereKey($payment->booking_invoice_id)->where('booking_id', $booking->id)->lockForUpdate()->firstOrFail();
            $payment = BookingInvoicePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->reversed_at) self::fail('A reversed payment cannot be allocated.');
            if ($existing = BookingDepositEntry::where('submission_id', $submission)->first()) {
                if ($existing->booking_invoice_payment_id !== $payment->id || self::cents($existing->amount) !== self::cents($amount)) {
                    self::fail('This submission has already been used.');
                }

                return $existing;
            }
            $allocatedInvoice = BookingDepositEntry::where('booking_invoice_id', $invoice->id)->where('kind', 'received')->sum('amount');
            $allocatedPayment = BookingDepositEntry::where('booking_invoice_payment_id', $payment->id)->where('kind', 'received')->sum('amount');
            $required = (float) (($invoice->fees ?? [])['Security Deposit'] ?? 0);
            if ($payment->rent_amount !== null && self::cents($amount) + self::cents($allocatedPayment) + self::cents($payment->rent_amount) > self::cents($payment->amount)) {
                self::fail('This allocation would consume rent already credited to the owner. Correct the rent allocation first.');
            }
            if (self::cents($amount) <= 0 || self::cents($amount) > self::cents($required) - self::cents($allocatedInvoice) || self::cents($amount) > self::cents($payment->amount) - self::cents($allocatedPayment)) {
                self::fail('The allocation exceeds the recorded payment or the invoice deposit charge.');
            }
            $source = AccountingEntry::whereKey($payment->accounting_entry_id)->lockForUpdate()->first();
            if (! $source || $source->booking_id !== $booking->id || $source->type !== 'income' || ! in_array($source->approval_status, ['posted', 'approved', 'paid']) || self::cents($source->credit) < self::cents($amount)) {
                self::fail('This payment has no compatible posted ledger receipt. Review the legacy payment before allocating it; do not collect again.');
            }
            $source->update(['credit' => round((float) $source->credit - $amount, 2), 'gross_amount' => round((float) $source->gross_amount - $amount, 2), 'net_amount' => round((float) $source->net_amount - $amount, 2)]);
            $ledger = self::ledger($booking, '2030', ['entry_date' => $payment->payment_date, 'credit' => $amount, 'gross_amount' => $amount, 'paid_from_account_id' => $source->paid_from_account_id, 'description' => 'Deposit allocation from '.$invoice->invoice_number, 'transaction_reference' => $payment->id]);

            return BookingDepositEntry::create(['booking_id' => $booking->id, 'booking_invoice_id' => $invoice->id, 'booking_invoice_payment_id' => $payment->id, 'kind' => 'received', 'amount' => $amount, 'entry_date' => $payment->payment_date, 'submission_id' => $submission, 'bank_account_id' => $payment->bank_account_id, 'accounting_entry_id' => $ledger->id, 'payment_method' => $payment->payment_method, 'reference' => $payment->reference, 'receipt_path' => $payment->receipt_path, 'notes' => 'Allocated from payment '.$payment->id.'; no additional collection.', 'created_by' => auth()->id()]);
        });
    }

    public static function requestRefund(Booking $booking, array $data): BookingDepositRefund
    {
        return DB::transaction(function () use ($booking, $data) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            self::assertNoOpenRequest($booking);
            $held = self::totals($booking)['held'];
            $deduction = round(collect($data['deductions'] ?? [])->sum('amount'), 2);
            if ($held <= 0 || $deduction < 0 || self::cents($deduction) > self::cents($held)) {
                self::fail('No available deposit, or deductions exceed the held balance.');
            }

            return BookingDepositRefund::create(['booking_id' => $booking->id, 'request_no' => 'RF-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)), 'held_at_request' => $held, 'deduction_amount' => $deduction, 'refund_amount' => round($held - $deduction, 2), 'deductions' => $data['deductions'] ?? [], 'reason' => $data['reason'], 'inspection_id' => $data['inspection_id'] ?? null, 'status' => 'pending', 'requested_by' => auth()->id()]);
        });
    }

    private static function assertNoOpenRequest(Booking $booking): void
    {
        if (BookingDepositRefund::where('booking_id', $booking->id)->whereIn('status', ['pending', 'approved'])->exists()) {
            self::fail('A refund request is already pending or awaiting payment. Complete or reject it first.');
        }
    }

    public static function review(BookingDepositRefund $refund, string $decision, string $notes): void
    {
        DB::transaction(function () use ($refund, $decision, $notes) {
            $booking = Booking::whereKey($refund->booking_id)->lockForUpdate()->firstOrFail();
            $refund = BookingDepositRefund::whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($refund->status !== 'pending') {
                self::fail('This request has already been reviewed.');
            }
            if (! in_array($decision, ['approved', 'rejected'])) {
                self::fail('Invalid review decision.');
            }
            if ($decision === 'approved') {
                if (self::cents(self::totals($booking)['held']) < self::cents($refund->held_at_request)) {
                    self::fail('The held deposit changed. Review the request again.');
                }
                if ((float) $refund->deduction_amount > 0) {
                    $ledger = self::ledger($booking, '2030', ['debit' => $refund->deduction_amount, 'description' => 'Approved deposit deduction '.$refund->request_no, 'transaction_reference' => $refund->request_no]);
                    self::ledger($booking, '2095', ['credit' => $refund->deduction_amount, 'description' => 'Deposit deduction pending final allocation '.$refund->request_no, 'transaction_reference' => $refund->request_no]);
                    BookingDepositEntry::create(['booking_id' => $booking->id, 'refund_id' => $refund->id, 'kind' => 'deducted', 'amount' => $refund->deduction_amount, 'entry_date' => today(), 'submission_id' => 'deduction:'.$refund->id, 'accounting_entry_id' => $ledger->id, 'notes' => $notes, 'created_by' => auth()->id()]);
                }
            }
            $refund->update(['status' => $decision === 'approved' && self::cents($refund->refund_amount) === 0 ? 'settled' : $decision, 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_notes' => $notes]);
        });
    }

    public static function pay(BookingDepositRefund $refund, array $data): BookingDepositEntry
    {
        return DB::transaction(function () use ($refund, $data) {
            $booking = Booking::whereKey($refund->booking_id)->lockForUpdate()->firstOrFail();
            $refund = BookingDepositRefund::whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($existing = BookingDepositEntry::where('submission_id', $data['submission_id'])->first()) {
                if ($existing->refund_id !== $refund->id || $existing->kind !== 'refunded' || self::cents($existing->amount) !== self::cents($data['amount']) || $existing->bank_account_id !== $data['bank_account_id'] || $existing->reference !== $data['reference'] || $existing->recipient !== $data['recipient']) {
                    self::fail('This payment submission has already been used.');
                }

                return $existing;
            }
            if ($refund->status !== 'approved') {
                self::fail('Only an approved refund can be paid.');
            }
            $amount = (float) $data['amount'];
            if (self::cents($amount) <= 0 || self::cents($amount) > self::cents($refund->remaining_amount) || self::cents($amount) > self::cents(self::totals($booking)['held'])) {
                self::fail('Refund exceeds the approved amount or held deposit.');
            }
            if (\Carbon\Carbon::parse($data['entry_date'])->lt($refund->reviewed_at->copy()->startOfDay())) {
                self::fail('Refund payment cannot be dated before approval.');
            }
            $account = BankAccount::whereKey($data['bank_account_id'])->where('is_active', true)->lockForUpdate()->firstOrFail();
            $balance = (float) $account->opening_balance + (float) $account->entries()->whereIn('approval_status', ['posted', 'approved', 'paid'])->selectRaw('COALESCE(SUM(credit - debit),0) AS movement')->value('movement');
            if (self::cents($amount) > self::cents($balance)) {
                self::fail('The selected bank/cash account has insufficient recorded balance.');
            }
            $ledger = self::ledger($booking, '2030', ['entry_date' => $data['entry_date'], 'debit' => $amount, 'gross_amount' => $amount, 'paid_from_account_id' => $account->id, 'payment_method' => $data['payment_method'], 'transaction_reference' => $data['reference'], 'attachment' => $data['receipt_path'], 'description' => 'Deposit refund '.$refund->request_no.' to '.$data['recipient']]);
            $entry = BookingDepositEntry::create(array_merge($data, ['booking_id' => $booking->id, 'refund_id' => $refund->id, 'kind' => 'refunded', 'accounting_entry_id' => $ledger->id, 'created_by' => auth()->id()]));
            $account->update(['current_balance' => round($balance - $amount, 2)]);
            if (self::cents($refund->remaining_amount) === 0) {
                $refund->update(['status' => 'settled']);
            }

            return $entry;
        });
    }

    public static function carry(Booking $booking, Booking $target, float $amount, string $submission): void
    {
        DB::transaction(function () use ($booking, $target, $amount, $submission) {
            Booking::whereIn('id', [$booking->id, $target->id])->orderBy('id')->lockForUpdate()->get();
            $booking->refresh();
            $target->refresh();
            if ($existing = BookingDepositEntry::where('submission_id', 'out:'.$submission)->first()) {
                if ($existing->booking_id !== $booking->id || $existing->related_booking_id !== $target->id || self::cents($existing->amount) !== self::cents($amount)) {
                    self::fail('This carry-forward submission has already been used.');
                }

                return;
            }
            self::assertNoOpenRequest($booking);
            self::assertNoOpenRequest($target);
            if ($target->renewed_from_booking_id !== $booking->id || $target->property_id !== $booking->property_id || $target->guest_passport_id_no !== $booking->guest_passport_id_no || ! in_array($target->status, ['confirmed', 'checked_in'])) {
                self::fail('Choose an active linked renewal for the same guest and unit.');
            }
            if ($target->invoices()->get()->sum(fn ($i) => (float) (($i->fees ?? [])['Security Deposit'] ?? 0)) > 0) {
                self::fail('The renewal already includes a new deposit charge. Use a renewal with no new deposit charge to avoid double collection.');
            }
            if (self::cents($amount) <= 0 || self::cents($amount) > self::cents(self::totals($booking)['held'])) {
                self::fail('Carry-forward exceeds the held deposit.');
            }
            foreach ([[$booking, $target, 'carry_out', 'out:'], [$target, $booking, 'carry_in', 'in:']] as [$from, $to, $kind, $prefix]) {
                $ledger = self::ledger($from, '2030', [$kind === 'carry_out' ? 'debit' : 'credit' => $amount, 'description' => 'Deposit '.$kind.' linked to '.$to->booking_reference, 'transaction_reference' => $submission]);
                BookingDepositEntry::create(['booking_id' => $from->id, 'related_booking_id' => $to->id, 'kind' => $kind, 'amount' => $amount, 'entry_date' => today(), 'submission_id' => $prefix.$submission, 'accounting_entry_id' => $ledger->id, 'notes' => 'Carried between linked contracts; no cash movement or new collection.', 'created_by' => auth()->id()]);
            }
        });
    }
}

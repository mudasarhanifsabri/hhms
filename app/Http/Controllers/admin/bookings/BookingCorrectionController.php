<?php

namespace App\Http\Controllers\admin\bookings;

use App\Http\Controllers\Controller;
use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\BookingDepositEntry;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
use App\Models\LandlordAccountEntry;
use App\Support\DepositWallet;
use App\Support\OwnerReceiptPosting;
use App\Support\FinancialApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingCorrectionController extends Controller
{
    private function authorizeFinancialCorrection(): void
    {
        abort_unless(request()->attributes->get('financial_approval_execution') || auth()->user()?->hasAnyRole(['Super Administrator', 'Backend IT']), 403,
            'Only Super Administrator or Backend IT can correct or delete recorded financial entries.');
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['correction' => $message]);
    }

    public function invoice(Request $request, BookingInvoice $invoice)
    {
        $this->authorizeFinancialCorrection();
        $data = $request->validate(['rent_amount' => 'required|numeric|min:0|decimal:0,2',
            'vat_included' => 'nullable|boolean',
            'vat_rate' => 'required|numeric|min:0|max:100', 'fees' => 'nullable|array',
            'fees.*' => 'required|numeric|min:0|decimal:0,2', 'reason' => 'required|string|min:5|max:1000',
            'current_password' => FinancialApproval::trusted($request) ? 'nullable' : 'required|string|max:255']);
        if (! FinancialApproval::trusted($request) && ! Hash::check((string) $data['current_password'], (string) auth()->user()?->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your password is incorrect.']);
        }
        if (! FinancialApproval::trusted($request)) {
            if (\App\Models\FinancialApprovalRequest::where('status', 'pending')->where('type', 'invoice_edit')->where('booking_invoice_id', $invoice->id)->exists()) {
                throw ValidationException::withMessages(['correction' => 'This invoice already has a pending edit request.']);
            }
            $payload = collect($data)->except('current_password')->all();
            $approval = FinancialApproval::submit('invoice_edit', $payload, [
                'booking_id' => $invoice->booking_id, 'booking_invoice_id' => $invoice->id,
            ], null, $invoice->only(['rent_amount', 'vat_rate', 'vat_included', 'vat_amount', 'fees', 'total_amount']));
            $invoice->booking->histories()->create(['title' => 'Invoice Edit Approval Requested', 'description' => 'Request '.$approval->approval_no.' for '.$invoice->invoice_number.' submitted by '.auth()->user()->name.'. Invoice remains unchanged until approved.']);
            return back()->with('success', 'Invoice edit submitted for Admin/Accounting approval. The invoice remains unchanged.');
        }
        DB::transaction(function () use ($invoice, $data) {
            $booking = Booking::whereKey($invoice->booking_id)->lockForUpdate()->firstOrFail();
            $invoice = BookingInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($invoice->payments()->exists() || $invoice->status !== 'unpaid' || BookingDepositEntry::where('booking_invoice_id', $invoice->id)->exists()) {
                $this->fail('Only unpaid invoices without active payments or deposit activity can be edited. Reverse an incorrect eligible payment first; do not delete it.');
            }
            $fees = $invoice->fees ?? [];
            foreach ($data['fees'] ?? [] as $label => $value) {
                if (! array_key_exists($label, $fees)) {
                    $this->fail('Unknown invoice charge.');
                }
                $fees[$label] = (float) $value;
            }
            $before = $invoice->only(['rent_amount', 'vat_rate', 'vat_included', 'vat_amount', 'fees', 'total_amount']);
            $included = (bool) ($data['vat_included'] ?? false);
            $entered = round((float) $data['rent_amount'], 2);
            $rent = $included ? round($entered / (1 + (float) $data['vat_rate'] / 100), 2) : $entered;
            $rentVat = $included ? round($entered - $rent, 2) : round($rent * (float) $data['vat_rate'] / 100, 2);
            $taxableFeeVat = $invoice->vat_scope === 'rent_cleaning_agency'
                ? round(((float)($fees['Cleaning Fee'] ?? 0) + (float)($fees['Agency Fee'] ?? 0)) * (float)$data['vat_rate'] / 100, 2)
                : 0;
            $vat = $rentVat + $taxableFeeVat;
            $invoice->update(['rent_amount' => $rent, 'vat_rate' => $data['vat_rate'], 'vat_included' => $included,
                'vat_amount' => $vat, 'fees' => $fees, 'total_amount' => round($rent + $vat + array_sum($fees), 2)]);
            if ($invoice->invoice_type !== 'extension') {
                $fee = round((float) $invoice->rent_amount * (float) $booking->management_fee_percent / 100, 2);
                $booking->update(['rent_amount' => $invoice->rent_amount, 'vat_included' => $included, 'vat_amount' => $vat, 'total_amount' => $invoice->total_amount,
                    'management_fee_amount' => $fee, 'owner_rent_income' => (float) $invoice->rent_amount - $fee,
                    'security_deposit' => $fees['Security Deposit'] ?? 0, 'dtcm_fee' => $fees['DTCM Fee'] ?? 0,
                    'cleaning_fee' => $fees['Cleaning Fee'] ?? 0, 'agency_fee' => $fees['Agency Fee'] ?? 0]);
            }
            if ($booking->owner_posting_basis === 'legacy') {
                $reference = $invoice->invoice_type === 'original' ? $booking->booking_reference : $invoice->invoice_number;
                $rows = LandlordAccountEntry::where('reference', $reference)->where('property_id', $booking->property_id)->whereIn('type', ['rent_income', 'management_fee'])->get();
                foreach ($rows as $row) {
                    $row->update(['amount' => $row->type === 'rent_income' ? $invoice->rent_amount : round((float) $invoice->rent_amount * (float) $booking->management_fee_percent / 100, 2)]);
                }
                foreach ($rows->pluck('landlord_id')->unique() as $id) {
                    LandlordAccountEntry::recalculateBalancesFor($id);
                }
            }
            $booking->histories()->create(['title' => 'Invoice Corrected', 'description' => $invoice->invoice_number.' by '.auth()->user()->name.'. Reason: '.$data['reason'].' | Before: '.json_encode($before).' | After: '.json_encode($invoice->only(array_keys($before)))]);
        });

        return back()->with('success', 'Invoice corrected; linked booking amounts were synchronized.');
    }

    public function paymentDetails(Request $request, BookingInvoicePayment $payment)
    {
        $this->authorizeFinancialCorrection();
        $data = $request->validate(['reference' => 'required|string|max:150', 'notes' => 'nullable|string|max:2000',
            'reason' => 'required|string|min:5|max:1000']);
        if (! FinancialApproval::trusted($request)) {
            FinancialApproval::assertNoPendingPaymentChange($payment->id);
            $approval = FinancialApproval::submit('payment_edit', $data, [
                'booking_id' => $payment->invoice->booking_id, 'booking_invoice_id' => $payment->booking_invoice_id,
                'booking_invoice_payment_id' => $payment->id,
            ], null, $payment->only(['reference', 'notes']));
            $payment->invoice->booking->histories()->create(['title' => 'Payment Edit Approval Requested', 'description' => 'Request '.$approval->approval_no.' for payment '.$payment->transaction_no.' submitted by '.auth()->user()->name.'. Original details remain active until approved.']);
            return back()->with('success', 'Payment edit submitted for Admin/Accounting approval. The original details remain unchanged.');
        }
        DB::transaction(function () use ($payment, $data) {
            $booking = Booking::whereKey($payment->invoice->booking_id)->lockForUpdate()->firstOrFail();
            $payment = BookingInvoicePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->reversed_at) {
                $this->fail('Reversed payments are read-only.');
            }
            $before = $payment->only(['reference', 'notes']);
            $relatedPayments = $payment->payment_batch_id
                ? BookingInvoicePayment::where('payment_batch_id', $payment->payment_batch_id)->lockForUpdate()->get()
                : collect([$payment]);
            foreach ($relatedPayments as $relatedPayment) {
                $relatedPayment->update(['reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null]);
                AccountingEntry::whereIn('id', $relatedPayment->allocation_entry_ids ?? [])->update(['transaction_reference' => $data['reference'] ?: $relatedPayment->invoice->invoice_number]);
                BookingDepositEntry::where('booking_invoice_payment_id', $relatedPayment->id)->where('kind', 'received')->update(['reference' => $data['reference']]);
            }
            AccountingEntry::whereKey($payment->accounting_entry_id)->update(['transaction_reference' => $data['reference'] ?: $payment->invoice->invoice_number]);
            if ($payment->payment_batch_id) {
                DB::table('booking_payment_batches')->where('id', $payment->payment_batch_id)->update(['reference' => $data['reference'], 'updated_at' => now()]);
            }
            $booking->histories()->create(['title' => 'Payment Details Corrected', 'description' => $payment->transaction_no.' by '.auth()->user()->name.'. Reason: '.$data['reason'].' | Before: '.json_encode($before).' | After: '.json_encode($payment->only(['reference', 'notes']))]);
        });

        return back()->with('success', 'Payment reference and notes updated. Financial amounts were not changed.');
    }

    public function reversePayment(Request $request, BookingInvoicePayment $payment)
    {
        $this->authorizeFinancialCorrection();
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000', 'confirm' => 'accepted']);
        if (! FinancialApproval::trusted($request)) {
            FinancialApproval::assertNoPendingPaymentChange($payment->id);
            $approval = FinancialApproval::submit('payment_reverse', $data, [
                'booking_id' => $payment->invoice->booking_id, 'booking_invoice_id' => $payment->booking_invoice_id,
                'booking_invoice_payment_id' => $payment->id,
            ], null, $payment->only(['payment_date', 'amount', 'payment_method', 'bank_account_id', 'reference', 'notes']));
            $payment->invoice->booking->histories()->create(['title' => 'Payment Deletion Approval Requested', 'description' => 'Request '.$approval->approval_no.' for payment '.$payment->transaction_no.' submitted by '.auth()->user()->name.'. Payment remains active until approved.']);
            return back()->with('success', 'Payment deletion submitted for Admin/Accounting approval. It remains active until approved.');
        }
        DB::transaction(function () use ($payment, $data) {
            $booking = Booking::whereKey($payment->invoice->booking_id)->lockForUpdate()->firstOrFail();
            $invoice = BookingInvoice::whereKey($payment->booking_invoice_id)->lockForUpdate()->firstOrFail();
            $payment = BookingInvoicePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->reversed_at) {
                $this->fail('This payment has already been reversed.');
            }
            if ($payment->payment_batch_id) {
                $this->fail('This payment belongs to a combined bank transfer. Reverse or correct the complete transfer as one batch; an individual invoice allocation cannot be reversed.');
            }
            if ($booking->owner_posting_basis !== 'receipts') {
                $this->fail('Legacy owner postings require reconciliation before reversing money. You can correct the reference and notes now.');
            }
            $depositAmount = (float) BookingDepositEntry::where('booking_invoice_payment_id', $payment->id)->where('kind', 'received')->sum('amount');
            $source = AccountingEntry::whereKey($payment->accounting_entry_id)->lockForUpdate()->first();
            if (! $source || $source->booking_id !== $booking->id || DepositWallet::cents((float)$source->credit + $depositAmount) !== DepositWallet::cents($payment->amount) || (float) $source->debit !== 0.0 || ! in_array($source->approval_status, ['posted', 'approved', 'paid'])) {
                $this->fail('The original ledger does not match this payment. Reconcile it before reversal.');
            }
            if (DepositWallet::cents($source->credit) > 0) {
                $reversal = $source->replicate();
                $reversal->fill(['entry_no' => 'REV-'.Str::upper(Str::random(16)), 'entry_date' => today(),
                    'credit' => 0, 'debit' => $source->credit, 'net_amount' => -(float) $source->net_amount,
                    'gross_amount' => -(float) $source->gross_amount, 'vat_amount' => -(float) $source->vat_amount,
                    'description' => 'Correction reversal of '.$source->entry_no.': '.$data['reason'], 'created_by' => auth()->id()]);
                $reversal->save();
            }
            DepositWallet::reversePaymentAllocation($payment, $data['reason']);
            $payment->update(['reversed_at' => now()]);
            OwnerReceiptPosting::reverse($payment, $data['reason']);
            \App\Support\InvoiceSettlement::reverse($payment, $data['reason']);
            $paid = (float) $invoice->payments()->sum('amount');
            $invoice->update(['status' => $paid <= 0 ? 'unpaid' : ($paid >= (float) $invoice->total_amount ? 'paid' : 'partial')]);
            \App\Support\BookingPaymentSummary::sync($booking);
            if ($payment->bank_account_id) {
                $account = BankAccount::whereKey($payment->bank_account_id)->lockForUpdate()->firstOrFail();
                $account->update(['current_balance' => (float) $account->opening_balance + (float) $account->entries()->whereIn('approval_status', ['posted', 'approved', 'paid'])->selectRaw('COALESCE(SUM(credit - debit),0) as movement')->value('movement')]);
            }
            $booking->histories()->create(['title' => 'Payment Reversed', 'description' => $payment->transaction_no.' / '.$invoice->invoice_number.' — AED '.$payment->amount.' by '.auth()->user()->name.'. Reason: '.$data['reason'].'. Original payment preserved; replacement must be entered separately.']);
        });

        return back()->with('success', 'Incorrect recorded payment removed from active totals and reversed with an audit trail. Record the correct replacement payment on the invoice. This did not send a bank refund.');
    }
}

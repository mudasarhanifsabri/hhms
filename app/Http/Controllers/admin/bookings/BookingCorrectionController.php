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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingCorrectionController extends Controller
{
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['correction' => $message]);
    }

    public function invoice(Request $request, BookingInvoice $invoice)
    {
        $data = $request->validate(['rent_amount' => 'required|numeric|min:0|decimal:0,2',
            'vat_rate' => 'required|numeric|min:0|max:100', 'fees' => 'nullable|array',
            'fees.*' => 'required|numeric|min:0|decimal:0,2', 'reason' => 'required|string|min:5|max:1000']);
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
            $before = $invoice->only(['rent_amount', 'vat_rate', 'vat_amount', 'fees', 'total_amount']);
            $vat = round((float) $data['rent_amount'] * (float) $data['vat_rate'] / 100, 2);
            $invoice->update(['rent_amount' => $data['rent_amount'], 'vat_rate' => $data['vat_rate'],
                'vat_amount' => $vat, 'fees' => $fees, 'total_amount' => round((float) $data['rent_amount'] + $vat + array_sum($fees), 2)]);
            if ($invoice->invoice_type !== 'extension') {
                $fee = round((float) $invoice->rent_amount * (float) $booking->management_fee_percent / 100, 2);
                $booking->update(['rent_amount' => $invoice->rent_amount, 'vat_amount' => $vat, 'total_amount' => $invoice->total_amount,
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
        $data = $request->validate(['reference' => 'nullable|string|max:150', 'notes' => 'nullable|string|max:2000',
            'reason' => 'required|string|min:5|max:1000']);
        DB::transaction(function () use ($payment, $data) {
            $booking = Booking::whereKey($payment->invoice->booking_id)->lockForUpdate()->firstOrFail();
            $payment = BookingInvoicePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->reversed_at) {
                $this->fail('Reversed payments are read-only.');
            }
            $before = $payment->only(['reference', 'notes']);
            $payment->update(['reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null]);
            AccountingEntry::whereKey($payment->accounting_entry_id)->update(['transaction_reference' => $payment->reference ?: $payment->invoice->invoice_number]);
            BookingDepositEntry::where('booking_invoice_payment_id', $payment->id)->where('kind', 'received')->update(['reference' => $payment->reference]);
            $booking->histories()->create(['title' => 'Payment Details Corrected', 'description' => $payment->id.' by '.auth()->user()->name.'. Reason: '.$data['reason'].' | Before: '.json_encode($before).' | After: '.json_encode($payment->only(['reference', 'notes']))]);
        });

        return back()->with('success', 'Payment reference and notes updated. Financial amounts were not changed.');
    }

    public function reversePayment(Request $request, BookingInvoicePayment $payment)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000', 'confirm' => 'accepted']);
        DB::transaction(function () use ($payment, $data) {
            $booking = Booking::whereKey($payment->invoice->booking_id)->lockForUpdate()->firstOrFail();
            $invoice = BookingInvoice::whereKey($payment->booking_invoice_id)->lockForUpdate()->firstOrFail();
            $payment = BookingInvoicePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->reversed_at) {
                $this->fail('This payment has already been reversed.');
            }
            if ($booking->owner_posting_basis !== 'receipts') {
                $this->fail('Legacy owner postings require reconciliation before reversing money. You can correct the reference and notes now.');
            }
            if (BookingDepositEntry::where('booking_invoice_payment_id', $payment->id)->exists()) {
                $this->fail('This payment is linked to a deposit wallet. Deposit-linked amounts are locked to protect refunds and carry-forward.');
            }
            $source = AccountingEntry::whereKey($payment->accounting_entry_id)->lockForUpdate()->first();
            if (! $source || $source->booking_id !== $booking->id || DepositWallet::cents($source->credit) !== DepositWallet::cents($payment->amount) || (float) $source->debit !== 0.0 || ! in_array($source->approval_status, ['posted', 'approved', 'paid'])) {
                $this->fail('The original ledger does not match this payment. Reconcile it before reversal.');
            }
            $reversal = $source->replicate();
            $reversal->fill(['entry_no' => 'REV-'.Str::upper(Str::random(16)), 'entry_date' => today(),
                'credit' => 0, 'debit' => $payment->amount, 'net_amount' => -(float) $source->net_amount,
                'gross_amount' => -(float) $source->gross_amount, 'vat_amount' => -(float) $source->vat_amount,
                'description' => 'Correction reversal of '.$source->entry_no.': '.$data['reason'], 'created_by' => auth()->id()]);
            $reversal->save();
            $payment->update(['reversed_at' => now()]);
            OwnerReceiptPosting::reverse($payment, $data['reason']);
            $paid = (float) $invoice->payments()->sum('amount');
            $invoice->update(['status' => $paid <= 0 ? 'unpaid' : ($paid >= (float) $invoice->total_amount ? 'paid' : 'partial')]);
            $booking->update(['invoice_status' => $booking->invoices()->where('status', '!=', 'paid')->exists() ? 'unpaid' : 'paid']);
            if ($payment->bank_account_id) {
                $account = BankAccount::whereKey($payment->bank_account_id)->lockForUpdate()->firstOrFail();
                $account->update(['current_balance' => (float) $account->opening_balance + (float) $account->entries()->whereIn('approval_status', ['posted', 'approved', 'paid'])->selectRaw('COALESCE(SUM(credit - debit),0) as movement')->value('movement')]);
            }
            $booking->histories()->create(['title' => 'Payment Reversed', 'description' => $payment->id.' / '.$invoice->invoice_number.' — AED '.$payment->amount.' by '.auth()->user()->name.'. Reason: '.$data['reason'].'. Original payment preserved; replacement must be entered separately.']);
        });

        return back()->with('success', 'Incorrect payment reversed, with an audit trail. Record the correct replacement payment on the invoice. This did not send a bank refund.');
    }
}

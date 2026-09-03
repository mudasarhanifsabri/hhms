<?php

namespace App\Http\Controllers\admin\bookings;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\BookingDepositEntry;
use App\Models\BookingDepositRefund;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
use App\Support\DepositWallet;
use App\Support\MediaStorage;
use App\Support\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepositController extends Controller
{
    public function index(Booking $booking)
    {
        $booking->load(['property.building', 'invoices.payments', 'renewals', 'inspections']);
        $totals = DepositWallet::totals($booking);
        $entries = BookingDepositEntry::with(['creator', 'bankAccount', 'relatedBooking'])->where('booking_id', $booking->id)->orderBy('created_at')->get();
        $refunds = BookingDepositRefund::with(['requester', 'reviewer', 'entries', 'inspection'])->where('booking_id', $booking->id)->latest()->get();
        $accounts = BankAccount::where('is_active', true)->orderBy('name')->get();
        $allocations = BookingDepositEntry::where('booking_id', $booking->id)->where('kind', 'received')->selectRaw('booking_invoice_payment_id, SUM(amount) as total')->groupBy('booking_invoice_payment_id')->pluck('total', 'booking_invoice_payment_id');

        return view('admin.bookings.deposit-wallet', compact('booking', 'totals', 'entries', 'refunds', 'accounts', 'allocations'));
    }

    public function collect(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'invoice_id' => ['required', Rule::exists('booking_invoices', 'id')->where('booking_id', $booking->id)],
            'bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')->where('is_active', true)],
            'amount' => 'required|numeric|min:0.01|decimal:0,2',
            'reference' => 'required|string|max:150', 'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'submission_id' => 'required|uuid',
        ]);
        $invoice = BookingInvoice::findOrFail($data['invoice_id']);
        $request->merge(['deposit_amount' => $request->input('amount'), 'deposit_submission_id' => $data['submission_id']]);

        return app(BookingController::class)->recordInvoicePayment($request, $invoice);
    }

    public function allocate(Request $request, Booking $booking)
    {
        $data = $request->validate(['payment_id' => 'required|exists:booking_invoice_payments,id', 'amount' => 'required|numeric|min:0.01|decimal:0,2', 'submission_id' => 'required|uuid']);
        $payment = BookingInvoicePayment::findOrFail($data['payment_id']);
        abort_unless($payment->invoice?->booking_id === $booking->id, 404);
        DepositWallet::allocate($booking, $payment, (float) $data['amount'], $data['submission_id']);

        return back()->with('success', 'Deposit allocated from the existing payment. No new money was collected.');
    }

    public function requestRefund(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'reason' => 'required|string|min:5|max:2000',
            'inspection_id' => ['nullable', Rule::exists('booking_inspections', 'id')->where('booking_id', $booking->id)],
            'deductions' => 'nullable|array|max:20', 'deductions.*.description' => 'nullable|string|max:500',
            'deductions.*.amount' => 'nullable|numeric|min:0|decimal:0,2', 'deductions.*.evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
        $deductions = [];
        foreach ($data['deductions'] ?? [] as $index => $row) {
            if ((float) ($row['amount'] ?? 0) <= 0) {
                continue;
            }
            if (blank($row['description'] ?? null) || ! $request->hasFile('deductions.'.$index.'.evidence')) {
                throw ValidationException::withMessages(['deductions' => 'Every deduction requires a description and evidence attachment.']);
            }
            $deductions[] = ['description' => $row['description'], 'amount' => round((float) $row['amount'], 2), 'evidence' => MediaStorage::store($request->file('deductions.'.$index.'.evidence'), 'deposit_evidence')];
        }
        DepositWallet::requestRefund($booking, ['reason' => $data['reason'], 'inspection_id' => $data['inspection_id'] ?? null, 'deductions' => $deductions]);

        return back()->with('success', 'Refund request submitted for admin approval. No money has been refunded.');
    }

    public function review(Request $request, Booking $booking, BookingDepositRefund $refund)
    {
        abort_unless($refund->booking_id === $booking->id, 404);
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'review_notes' => 'required|string|min:3|max:2000']);
        DepositWallet::review($refund, $data['decision'], $data['review_notes']);

        return back()->with('success', 'Refund request '.$data['decision'].'. Approval alone does not pay the guest.');
    }

    public function pay(Request $request, Booking $booking, BookingDepositRefund $refund)
    {
        abort_unless($refund->booking_id === $booking->id, 404);
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|decimal:0,2', 'entry_date' => 'required|date|before_or_equal:today',
            'bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')->where('is_active', true)],
            'payment_method' => 'required|in:Bank Transfer,Cash,Card,Cheque', 'reference' => 'required|string|max:150',
            'recipient' => 'required|string|max:255', 'notes' => 'nullable|string|max:2000',
            'proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', 'submission_id' => 'required|uuid',
        ]);
        unset($data['proof']);
        $data['receipt_path'] = MediaStorage::store($request->file('proof'), 'deposit_refund_proofs');
        DepositWallet::pay($refund, $data);

        return back()->with('success', 'Actual refund payment recorded. Deposit and bank/cash balances updated.');
    }

    public function carry(Request $request, Booking $booking)
    {
        $data = $request->validate(['target_id' => 'required|exists:bookings,id', 'amount' => 'required|numeric|min:0.01|decimal:0,2', 'submission_id' => 'required|uuid']);
        DepositWallet::carry($booking, Booking::findOrFail($data['target_id']), (float) $data['amount'], $data['submission_id']);

        return back()->with('success', 'Deposit carried to the linked renewal. No cash movement or new deposit charge.');
    }

    public function receipt(Booking $booking, BookingDepositEntry $entry)
    {
        abort_unless($entry->booking_id === $booking->id && $entry->kind === 'refunded', 404);
        $entry->load(['booking.property.building', 'refund.reviewer', 'creator', 'bankAccount']);

        return PdfRenderer::downloadView('admin.bookings.pdf.deposit-refund', compact('entry'), 'deposit-refund-'.$entry->id.'.pdf');
    }
}

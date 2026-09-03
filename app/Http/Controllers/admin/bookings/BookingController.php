<?php

namespace App\Http\Controllers\admin\bookings;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
use App\Models\BookingTask;
use App\Models\AccountingAccount;
use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use App\Support\MediaStorage;
use App\Support\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = $this->filteredBookings($request);
        $totalBookings = Booking::count();
        $paidInvoices = Booking::where('invoice_status', 'paid')->count();
        $unpaidInvoices = Booking::where('invoice_status', 'unpaid')->count();

        return view('admin.bookings.index', compact('bookings', 'totalBookings', 'paidInvoices', 'unpaidInvoices'));
    }

    public function grid(Request $request)
    {
        $bookings = $this->filteredBookings($request);

        return view('admin.bookings.grid', compact('bookings'));
    }

    private function filteredBookings(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:200',
            'status' => 'nullable|in:confirmed,checked_in,checked_out',
            'invoice_status' => 'nullable|in:paid,unpaid,partial',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d'.($request->filled('from') ? '|after_or_equal:from' : ''),
            'per_page' => 'nullable|integer|in:10,12,25,50,100',
        ]);
        $query = Booking::with(['property.building', 'agent']);
        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where('booking_reference', 'like', $term)
                    ->orWhere('guest_name', 'like', $term)->orWhere('guest_email', 'like', $term)
                    ->orWhere('guest_phone', 'like', $term)->orWhere('invoice_number', 'like', $term)
                    ->orWhereHas('invoices', fn ($q) => $q->where('invoice_number', 'like', $term))
                    ->orWhereHas('property', fn ($q) => $q->where('name', 'like', $term)
                        ->orWhereHas('building', fn ($b) => $b->where('name', 'like', $term)))
                    ->orWhereHas('agent', fn ($q) => $q->where('name', 'like', $term));
            });
        }
        foreach (['status', 'invoice_status'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (!empty($filters['from'])) {
            $query->whereDate('check_in', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('check_in', '<=', $filters['to']);
        }

        return $query->latest()->orderByDesc('id')->paginate($filters['per_page'] ?? 12)->withQueryString();
    }

    public function create()
    {
        $properties = Property::with('building')->orderBy('name')->get();
        $agents = User::where('role', 'agent')->orderBy('name')->get();

        return view('admin.bookings.create', compact('properties', 'agents'));
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateBooking($request);
        $this->ensurePropertyCanBeBooked($validatedData['property_id'], $validatedData['check_in'], $validatedData['check_out']);
        $amounts = $this->calculateAmounts($validatedData, $request);
        $booking = Booking::create([
            ...$validatedData,
            'owner_posting_basis' => 'receipts',
            'guest_document' => $this->uploadFile($request, 'guest_document', 'booking_documents'),
            'booking_reference' => $this->nextReference('BK'),
            'invoice_number' => $this->nextReference('INV'),
            ...$amounts,
            'status' => 'confirmed',
            'invoice_status' => 'unpaid',
        ]);

        $booking->histories()->create([
            'title' => 'Booking Created',
            'description' => 'Booking and invoice were generated for ' . $booking->guest_name . '.',
        ]);

        $this->recordOwnerIncome($booking);
        $this->createBookingInvoice($booking);
        $this->markPropertyStatus($booking, 'booked');

        return redirect()->route('admin.booking.show', $booking->id)
            ->with('success', 'Booking created successfully.');
    }

    public function edit(Booking $booking)
    {
        $booking->load(['property', 'agent']);
        if ($booking->invoices()->exists()) {
            return view('admin.bookings.edit-guest', compact('booking'));
        }
        $properties = Property::with('building')->orderBy('name')->get();
        $agents = User::where('role', 'agent')->orderBy('name')->get();

        return view('admin.bookings.edit', compact('booking', 'properties', 'agents'));
    }

    public function update(Request $request, Booking $booking)
    {
        if ($booking->invoices()->exists()) {
            if (!$request->boolean('edit_details_only')) {
                return back()->withErrors(['invoice' => 'Use Edit Invoice for charges. Booking financial and stay details are locked once invoices exist.']);
            }
            $details = $request->validate(['guest_name' => 'required|string|max:255', 'guest_email' => 'required|email|max:255',
                'guest_phone' => 'required|string|max:50', 'notes' => 'nullable|string|max:2000', 'reason' => 'required|string|min:5|max:1000']);
            DB::transaction(function () use ($booking, $details) {
                $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
                $before = $booking->only(['guest_name','guest_email','guest_phone','notes']);
                $booking->update(\Illuminate\Support\Arr::only($details, array_keys($before)));
                $booking->histories()->create(['title' => 'Guest Details Corrected', 'description' => 'By '.auth()->user()->name.'. Reason: '.$details['reason'].' | Before: '.json_encode($before).' | After: '.json_encode($booking->only(array_keys($before)))]);
            });
            return redirect()->route('admin.booking.show', $booking)->with('success', 'Guest contact details updated. Invoice charges and payments are unchanged.');
        }
        $validatedData = $this->validateBooking($request);
        $this->ensurePropertyCanBeBooked($validatedData['property_id'], $validatedData['check_in'], $validatedData['check_out'], $booking->id);
        $amounts = $this->calculateAmounts($validatedData, $request);
        $payload = [
            ...$validatedData,
            ...$amounts,
        ];

        if ($request->hasFile('guest_document')) {
            $payload['guest_document'] = $this->uploadFile($request, 'guest_document', 'booking_documents');
        } else {
            unset($payload['guest_document']);
        }

        $booking->update($payload);
        $booking->histories()->create([
            'title' => 'Booking Updated',
            'description' => 'Booking details were updated.',
        ]);

        $this->syncOwnerIncome($booking);
        $this->markPropertyStatus($booking, 'booked');

        return redirect()->route('admin.booking.show', $booking->id)
            ->with('success', 'Booking updated successfully.');
    }

    public function destroy(Booking $booking)
    {
        if (\App\Models\BookingDepositEntry::where('booking_id', $booking->id)->exists() || \App\Models\BookingDepositRefund::where('booking_id', $booking->id)->exists()) {
            return back()->withErrors(['deposit' => 'This booking has deposit audit records and cannot be deleted.']);
        }
        if ($booking->invoices()->whereHas('allPayments')->exists()) {
            return back()->withErrors(['payment' => 'Bookings with payment history cannot be deleted. Use the audited correction options in History.']);
        }
        $reference = $booking->booking_reference;
        $landlordId = $booking->property?->landlord_id;

        LandlordAccountEntry::where('reference', $reference)->delete();
        $booking->tasks()->with('remarks')->get()->each(function (BookingTask $task) {
            $task->remarks()->delete();
            $task->delete();
        });
        $booking->histories()->delete();
        $booking->delete();

        if ($landlordId) {
            LandlordAccountEntry::recalculateBalancesFor($landlordId);
        }

        return redirect()->route('admin.booking.index')
            ->with('success', 'Booking deleted successfully.');
    }

    public function extend(Request $request, Booking $booking)
    {
        $validatedData = $request->validate([
            'check_out' => 'required|date|after:' . $booking->check_out?->toDateString(),
            'check_out_time' => 'nullable|date_format:H:i',
            'extension_rent_amount' => 'required|numeric|min:0',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'other_fees' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        $additionalRent = (float) ($validatedData['extension_rent_amount'] ?? 0);
        $oldCheckOut = $booking->check_out?->copy();
        $maximumContractCheckout = $booking->check_in?->copy()->addDays(90);

        if ($maximumContractCheckout && \Carbon\Carbon::parse($validatedData['check_out'])->gt($maximumContractCheckout)) {
            throw ValidationException::withMessages([
                'check_out' => 'This extension exceeds the 90-day contract limit. Create a contract renewal and charge the DTCM fee again.',
            ]);
        }

        $this->ensurePropertyCanBeBooked($booking->property_id, $booking->check_in?->toDateString(), $validatedData['check_out'], $booking->id);

        DB::transaction(function () use ($booking, $validatedData, $additionalRent, $oldCheckOut) {
            $booking->update([
                'check_out' => $validatedData['check_out'],
                'check_out_time' => $validatedData['check_out_time'] ?? $booking->check_out_time,
            ]);

            $invoice = $this->createBookingInvoice($booking, 'extension', [
                'period_from' => $oldCheckOut?->copy()->addDay() ?? $booking->check_in,
                'period_to' => $booking->check_out,
                'rent_amount' => $additionalRent,
                'vat_rate' => (float) $validatedData['vat_rate'],
                'fees' => ['Other Fees' => (float) ($validatedData['other_fees'] ?? 0)],
                'notes' => 'Extension invoice for booking ' . $booking->booking_reference,
            ]);

            $booking->histories()->create([
                'title' => 'Booking Extended',
                'description' => 'Extended until ' . $booking->check_out?->format('d M Y') . '. Separate invoice ' . $invoice->invoice_number . ' created for AED ' . number_format((float) $invoice->total_amount, 2) . '.',
            ]);
            $this->recordOwnerIncomeForInvoice($invoice);
        });

        return back()->with('success', 'Booking extended. A separate extension invoice and payment balance were created.');
    }

    public function renew(Request $request, Booking $booking)
    {
        $validatedData = $request->validate([
            'check_in' => 'required|date|after_or_equal:' . $booking->check_out?->toDateString(),
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out' => 'required|date|after:check_in',
            'check_out_time' => 'nullable|date_format:H:i',
            'rent_amount' => 'required|numeric|min:0',
            'dtcm_fee' => 'required|numeric|min:0.01',
            'cleaning_fee' => 'nullable|numeric|min:0',
            'agency_fee' => 'nullable|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (\Carbon\Carbon::parse($validatedData['check_in'])->diffInDays(\Carbon\Carbon::parse($validatedData['check_out'])) > 90) {
            throw ValidationException::withMessages(['check_out' => 'A renewed contract cannot exceed 90 days.']);
        }

        $payload = [
            'property_id' => $booking->property_id,
            'agent_id' => $booking->agent_id,
            'guest_name' => $booking->guest_name,
            'guest_email' => $booking->guest_email,
            'guest_phone' => $booking->guest_phone,
            'guest_passport_id_no' => $booking->guest_passport_id_no,
            'guest_document' => $booking->guest_document,
            'check_in' => $validatedData['check_in'],
            'check_in_time' => $validatedData['check_in_time'] ?? $booking->check_in_time,
            'check_out' => $validatedData['check_out'],
            'check_out_time' => $validatedData['check_out_time'] ?? $booking->check_out_time,
            'rent_amount' => $validatedData['rent_amount'],
            'vat_included' => $booking->vat_included,
            'dtcm_fee' => $validatedData['dtcm_fee'] ?? 0,
            'cleaning_fee' => $validatedData['cleaning_fee'] ?? 0,
            'agency_fee' => $validatedData['agency_fee'] ?? 0,
            'security_deposit' => $validatedData['security_deposit'] ?? 0,
            'notes' => $validatedData['notes'] ?? null,
        ];
        $this->ensurePropertyCanBeBooked($payload['property_id'], $payload['check_in'], $payload['check_out']);
        $amounts = $this->calculateAmounts($payload, new Request(['vat_included' => $booking->vat_included]));

        $newBooking = Booking::create([
            ...$payload,
            'owner_posting_basis' => 'receipts',
            'renewed_from_booking_id' => $booking->id,
            'booking_reference' => $this->nextReference('BK'),
            'invoice_number' => $this->nextReference('INV'),
            ...$amounts,
            'status' => 'confirmed',
            'invoice_status' => 'unpaid',
        ]);

        $newBooking->histories()->create([
            'title' => 'Booking Renewed',
            'description' => 'Renewed from booking ' . $booking->booking_reference . '.' . (! empty($validatedData['notes']) ? ' Notes: ' . $validatedData['notes'] : ''),
        ]);
        $booking->histories()->create([
            'title' => 'Renewal Created',
            'description' => 'Renewal booking ' . $newBooking->booking_reference . ' was created.',
        ]);

        $invoice = $this->createBookingInvoice($newBooking, 'renewal', [
            'vat_amount' => (float) $newBooking->vat_amount,
            'notes' => 'Renewal invoice from booking ' . $booking->booking_reference,
        ]);
        $this->recordOwnerIncomeForInvoice($invoice);
        $this->markPropertyStatus($newBooking, 'booked');

        return redirect()->route('admin.booking.show', $newBooking->id)
            ->with('success', 'Booking renewed successfully.');
    }

    public function show(Booking $booking)
    {
        $booking->load([
            'property.building',
            'agent',
            'histories',
            'inspections.items',
            'invoices.payments.bankAccount',
        ]);

        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();

        return view('admin.bookings.show', compact('booking', 'bankAccounts'));
    }

    public function recordInvoicePayment(Request $request, BookingInvoice $invoice)
    {
        $data = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'deposit_amount' => 'nullable|numeric|min:0|decimal:0,2|lte:amount',
            'rent_amount' => 'nullable|numeric|min:0|decimal:0,2|lte:amount',
            'deposit_submission_id' => 'nullable|uuid',
            'payment_method' => 'required|string|max:100',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'reference' => 'nullable|string|max:150',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string|max:2000',
        ]);

        $invoice->load('payments', 'booking.property');
        DB::transaction(function () use ($request, $invoice, $data) {
            Booking::whereKey($invoice->booking_id)->lockForUpdate()->firstOrFail();
            $invoice = BookingInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if (! empty($data['deposit_submission_id']) && ($existing = \App\Models\BookingDepositEntry::where('submission_id', $data['deposit_submission_id'])->first())) {
                if ($existing->booking_invoice_id !== $invoice->id || \App\Support\DepositWallet::cents($existing->amount) !== \App\Support\DepositWallet::cents($data['deposit_amount'] ?? 0)) {
                    throw ValidationException::withMessages(['deposit' => 'This deposit submission has already been used.']);
                }
                return;
            }
            $paidBefore = $invoice->paid_amount;
            if (\App\Support\DepositWallet::cents($data['amount']) > \App\Support\DepositWallet::cents($invoice->balance_due)) {
                throw ValidationException::withMessages(['amount' => 'Payment exceeds the current invoice balance.']);
            }
            if ($invoice->booking->owner_posting_basis === 'receipts') {
                if (!isset($data['rent_amount'])) {
                    throw ValidationException::withMessages(['rent_amount' => 'Enter the rent portion of this payment, excluding VAT, fees and security deposit.']);
                }
                $rent = (float) $data['rent_amount'];
                $deposit = (float) ($data['deposit_amount'] ?? 0);
                $rentPaid = (float) $invoice->payments()->sum('rent_amount');
                $depositPaid = (float) \App\Models\BookingDepositEntry::where('booking_invoice_id', $invoice->id)->where('kind', 'received')->sum('amount');
                $nonRentPaid = (float) $invoice->payments()->sum('amount') - $rentPaid - $depositPaid;
                $depositCharge = (float) (($invoice->fees ?? [])['Security Deposit'] ?? 0);
                $otherCharges = (float) $invoice->total_amount - (float) $invoice->rent_amount - $depositCharge;
                $otherPayment = (float) $data['amount'] - $rent - $deposit;
                if ($otherPayment < -0.001 || round($rentPaid + $rent, 2) > (float) $invoice->rent_amount || round($nonRentPaid + $otherPayment, 2) > round($otherCharges, 2)) {
                    throw ValidationException::withMessages(['rent_amount' => 'Payment allocation exceeds rent or other charges. Allocate the correct rent and deposit portions; the remainder covers VAT and other fees.']);
                }
            }
            $entry = AccountingEntry::create([
                'entry_no' => $this->nextAccountingEntryNumber(),
                'entry_date' => $data['payment_date'],
                'type' => 'income', 'category' => 'rental_income',
                'accounting_account_id' => AccountingAccount::where('code', '4010')->value('id'),
                'description' => 'Guest payment for ' . $invoice->invoice_number,
                'property_id' => $invoice->booking?->property_id,
                'landlord_id' => $invoice->booking?->property?->landlord_id,
                'booking_id' => $invoice->booking_id,
                'paid_from_account_id' => $data['bank_account_id'] ?? null,
                'debit' => 0, 'credit' => $data['amount'],
                'vat_rate' => $invoice->vat_rate, 'vat_amount' => 0,
                'net_amount' => $data['amount'], 'gross_amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'transaction_reference' => ($data['reference'] ?? null) ?: $invoice->invoice_number,
                'status' => 'posted', 'approval_status' => 'posted', 'created_by' => auth()->id(),
            ]);

            $payment = BookingInvoicePayment::create([
                'booking_invoice_id' => $invoice->id,
                'payment_date' => $data['payment_date'], 'amount' => $data['amount'],
                'payment_method' => $data['payment_method'], 'bank_account_id' => $data['bank_account_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'receipt_path' => $this->uploadFile($request, 'receipt', 'booking_payment_proofs'),
                'notes' => $data['notes'] ?? null, 'accounting_entry_id' => $entry->id, 'created_by' => auth()->id(),
                'rent_amount' => $invoice->booking->owner_posting_basis === 'receipts' ? $data['rent_amount'] : null,
            ]);

            if ((float) ($data['deposit_amount'] ?? 0) > 0) {
                \App\Support\DepositWallet::allocate($invoice->booking, $payment, (float) $data['deposit_amount'], $data['deposit_submission_id'] ?? 'payment:'.$payment->id);
            }

            $paid = $paidBefore + (float) $data['amount'];
            \App\Support\OwnerReceiptPosting::post($payment);
            $invoice->booking->histories()->create(['title' => 'Payment Recorded', 'description' => 'Payment '.$payment->id.' for '.$invoice->invoice_number.': AED '.number_format((float)$payment->amount, 2).'; rent portion '.($payment->rent_amount ?? 'legacy').'; recorded by '.auth()->user()->name.'.']);
            $invoice->update(['status' => $paid >= (float) $invoice->total_amount ? 'paid' : 'partial']);
            $invoice->booking?->update(['invoice_status' => $invoice->booking->invoices()->where('status', '!=', 'paid')->exists() ? 'unpaid' : 'paid']);
            if ($data['bank_account_id'] ?? null) {
                $account = BankAccount::whereKey($data['bank_account_id'])->lockForUpdate()->first();
                $account?->forceFill(['current_balance' => (float) $account->opening_balance + (float) $account->entries()->whereIn('approval_status', ['posted', 'approved', 'paid'])->selectRaw('COALESCE(SUM(credit - debit),0) as movement')->value('movement')])->save();
            }
        });

        return back()->with('success', 'Payment recorded against ' . $invoice->invoice_number . '.');
    }

    public function paymentReceipt(BookingInvoice $invoice)
    {
        $invoice->load(['booking.property.building', 'payments.bankAccount']);
        abort_if($invoice->payments->isEmpty(), 422, 'No itemised payment records exist for this invoice. A receipt cannot be generated from an invoice status alone.');
        return PdfRenderer::downloadView('admin.bookings.pdf.payment-receipt', compact('invoice'), $invoice->invoice_number.'-receipt.pdf');
    }

    public function history(Booking $booking)
    {
        $booking->load(['property', 'agent', 'histories', 'invoices.allPayments.bankAccount']);

        return view('admin.bookings.history', compact('booking'));
    }

    public function attachPaymentProof(Request $request, Booking $booking)
    {
        return back()->withErrors(['payment' => 'Use Record Payment on the invoice to enter the actual amount, account, rent allocation and deposit portion. Uploading proof alone cannot mark an invoice paid.']);
    }

    private function nextAccountingEntryNumber(): string
    {
        do {
            $number = 'JE-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
        } while (AccountingEntry::where('entry_no', $number)->exists());

        return $number;
    }

    public function checkIn(Request $request, Booking $booking)
    {
        if ($booking->invoice_status !== 'paid') {
            return back()->withErrors(['workflow' => 'Invoice must be paid before check in.']);
        }

        $booking->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);
        $this->markPropertyStatus($booking, 'booked');

        $booking->histories()->create([
            'title' => 'Guest Checked In',
            'description' => 'Guest inspection list was completed for arrival.',
        ]);

        return back()->with('success', 'Guest check in completed.');
    }

    public function checkOut(Request $request, Booking $booking)
    {
        $data = $request->validate(['checkout_confirmation' => 'required|string']);
        $confirmation = $request->session()->get('checkout_confirmation.'.$booking->id);
        if (! $confirmation || ! hash_equals($confirmation['token'], $data['checkout_confirmation']) || time() - $confirmation['issued_at'] < 5 || time() - $confirmation['issued_at'] > 600) {
            throw ValidationException::withMessages(['checkout' => 'Open the checkout confirmation and wait five seconds before confirming.']);
        }
        if (! in_array($booking->status, ['confirmed', 'checked_in'])) {
            throw ValidationException::withMessages(['checkout' => 'Only an active booking can be checked out.']);
        }
        DB::transaction(function () use ($booking) {
        $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
        if (! in_array($booking->status, ['confirmed', 'checked_in'])) {
            throw ValidationException::withMessages(['checkout' => 'This booking has already been checked out.']);
        }
        $booking->update([
            'status' => 'checked_out',
            'checked_out_at' => now(),
        ]);

        $this->createCheckoutTasks($booking);
        $this->markPropertyStatus($booking, 'under_cleaning');

        $booking->histories()->create([
            'title' => 'Guest Checked Out',
            'description' => 'Cleaning, maintenance, and check out inspection tasks were created.',
        ]);
        });
        $request->session()->forget('checkout_confirmation.'.$booking->id);

        return back()->with('success', 'Check out completed and tasks created.');
    }

    public function prepareCheckout(Request $request, Booking $booking)
    {
        abort_unless(in_array($booking->status, ['confirmed', 'checked_in']), 422, 'Booking is not active.');
        $token = Str::random(48);
        $request->session()->put('checkout_confirmation.'.$booking->id, ['token' => $token, 'issued_at' => time()]);
        return response()->json(['token' => $token]);
    }

    public function reverseCheckout(Request $request, Booking $booking)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000']);
        \App\Support\BookingCheckout::reverse($booking, $data['reason']);
        return back()->with('success', 'Checkout reversed. Unstarted checkout tasks were cancelled; payment records were preserved.');
    }

    public function invoice(Booking $booking)
    {
        $booking->load(['property.building', 'agent']);

        return PdfRenderer::downloadView('admin.bookings.pdf.invoice', compact('booking'), $booking->invoice_number . '.pdf');
    }

    public function confirmation(Booking $booking)
    {
        $booking->load(['property.building', 'agent']);

        return PdfRenderer::downloadView('admin.bookings.pdf.confirmation', compact('booking'), $booking->booking_reference . '-confirmation.pdf');
    }

    private function uploadFile(Request $request, string $field, string $folder): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        return MediaStorage::store($request->file($field), $folder);
    }

    private function createCheckoutTasks(Booking $booking): void
    {
        $tasks = [
            'cleaning' => 'Cleaning after guest check out',
            'maintenance' => 'Maintenance review after guest check out',
            'checkout_inspection' => 'Check out inspection',
        ];

        foreach ($tasks as $type => $title) {
            if ($booking->tasks()->where('type', $type)->where('status', '!=', 'cancelled')->exists()) {
                continue;
            }

            $booking->tasks()->create([
                'task_number' => $this->nextTaskNumber(),
                'property_id' => $booking->property_id,
                'created_by' => auth()->id(),
                'type' => $type,
                'category' => $type,
                'priority' => 'medium',
                'status' => 'new',
                'progress' => 0,
                'title' => $title,
                'description' => 'Auto created from booking check out.',
            ])->activities()->create([
                'user_id' => auth()->id(),
                'action' => 'Created',
                'comment' => 'Auto created from booking check out.',
            ]);
        }
    }

    private function ensurePropertyCanBeBooked(string $propertyId, string $checkIn, string $checkOut, ?string $ignoreBookingId = null): void
    {
        $overlap = Booking::where('property_id', $propertyId)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->when($ignoreBookingId, fn ($query) => $query->where('id', '!=', $ignoreBookingId))
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('check_in', '<', $checkOut)
                    ->where('check_out', '>', $checkIn);
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'property_id' => 'This unit already has a confirmed booking for the selected dates.',
            ]);
        }
    }

    private function markPropertyStatus(Booking $booking, string $status): void
    {
        $booking->property?->update(['status' => match ($status) { 'booked' => 'rented', 'under_cleaning' => 'vacant', default => $status }]);
    }

    private function nextTaskNumber(): string
    {
        do {
            $number = 'TSK-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
        } while (BookingTask::where('task_number', $number)->exists());

        return $number;
    }

    private function nextReference(string $prefix): string
    {
        do {
            $reference = $prefix . '-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
        } while (Booking::where($prefix === 'INV' ? 'invoice_number' : 'booking_reference', $reference)->exists());

        return $reference;
    }

    private function nextInvoiceNumber(string $prefix = 'INV'): string
    {
        do {
            $reference = $prefix . '-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
        } while (BookingInvoice::where('invoice_number', $reference)->exists() || Booking::where('invoice_number', $reference)->exists());

        return $reference;
    }

    private function createBookingInvoice(Booking $booking, string $type = 'original', array $override = []): BookingInvoice
    {
        $rentAmount = (float) ($override['rent_amount'] ?? $booking->rent_amount);
        $vatRate = (float) ($override['vat_rate'] ?? 5.0);
        $vatAmount = array_key_exists('vat_amount', $override)
            ? (float) $override['vat_amount']
            : round($rentAmount * ($vatRate / 100), 2);
        $fees = $override['fees'] ?? [
            'DTCM Fee' => (float) $booking->dtcm_fee,
            'Cleaning Fee' => (float) $booking->cleaning_fee,
            'Agency Fee' => (float) $booking->agency_fee,
            'Security Deposit' => (float) $booking->security_deposit,
        ];
        $feeTotal = collect($fees)->sum(fn ($amount) => (float) $amount);

        if ($type === 'original') {
            $vatAmount = (float) $booking->vat_amount;
        }

        return BookingInvoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => $type === 'original' ? $booking->invoice_number : $this->nextInvoiceNumber($type === 'extension' ? 'INV-EXT' : 'INV-REN'),
            'invoice_type' => $type,
            'issue_date' => now()->toDateString(),
            'period_from' => $override['period_from'] ?? $booking->check_in,
            'period_to' => $override['period_to'] ?? $booking->check_out,
            'rent_amount' => $rentAmount,
            'vat_rate' => $vatRate,
            'vat_included' => $type === 'extension' ? false : (bool) $booking->vat_included,
            'vat_amount' => $vatAmount,
            'fees' => $fees,
            'total_amount' => $rentAmount + $vatAmount + $feeTotal,
            'status' => 'unpaid',
            'notes' => $override['notes'] ?? null,
        ]);
    }

    private function recordOwnerIncomeForInvoice(BookingInvoice $invoice): void
    {
        $booking = $invoice->booking()->with('property')->first();
        if ($booking?->owner_posting_basis === 'receipts') return;
        $property = $booking?->property;
        if (! $property?->landlord_id) return;

        $reference = $invoice->invoice_number;
        $description = $invoice->type_label . ' rent for ' . ($property->name ?? 'Unit') . ' - ' . $invoice->period_from?->format('d M Y') . ' to ' . $invoice->period_to?->format('d M Y');
        LandlordAccountEntry::firstOrCreate(['landlord_id' => $property->landlord_id, 'reference' => $reference, 'type' => 'rent_income'], [
            'property_id' => $property->id, 'entry_date' => $invoice->period_from ?? now(), 'direction' => 'credit',
            'amount' => $invoice->rent_amount, 'description' => $description,
        ]);

        $rate = (float) ($property->management_fee_percent ?? 0);
        $fee = round((float) $invoice->rent_amount * $rate / 100, 2);
        if ($fee > 0) {
            LandlordAccountEntry::firstOrCreate(['landlord_id' => $property->landlord_id, 'reference' => $reference, 'type' => 'management_fee'], [
                'property_id' => $property->id, 'entry_date' => $invoice->period_from ?? now(), 'direction' => 'debit',
                'amount' => $fee, 'description' => 'Management fee ' . number_format($rate, 2) . '% on ' . $description,
            ]);
        }
        LandlordAccountEntry::recalculateBalancesFor($property->landlord_id);
    }

    private function validateBooking(Request $request): array
    {
        return $request->validate([
            'property_id' => 'required|exists:properties,id',
            'agent_id' => 'nullable|exists:users,id',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'required|string|max:50',
            'guest_passport_id_no' => 'required|string|max:100',
            'guest_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'check_in' => 'required|date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out' => 'required|date|after:check_in',
            'check_out_time' => 'nullable|date_format:H:i',
            'rent_amount' => 'required|numeric|min:0',
            'vat_included' => 'nullable|boolean',
            'dtcm_fee' => 'nullable|numeric|min:0',
            'cleaning_fee' => 'nullable|numeric|min:0',
            'agency_fee' => 'nullable|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    private function calculateAmounts(array $validatedData, Request $request): array
    {
        $property = Property::findOrFail($validatedData['property_id']);
        $rentAmount = (float) $validatedData['rent_amount'];
        $vatIncluded = $request->boolean('vat_included');
        $vatAmount = $vatIncluded ? round($rentAmount - ($rentAmount / 1.05), 2) : round($rentAmount * 0.05, 2);
        $baseRent = $vatIncluded ? round($rentAmount - $vatAmount, 2) : $rentAmount;
        $managementFeePercent = (float) ($property->management_fee_percent ?? 0);
        $managementFeeAmount = round($baseRent * ($managementFeePercent / 100), 2);
        $ownerRentIncome = round($baseRent - $managementFeeAmount, 2);
        $fees = [
            'dtcm_fee' => (float) ($validatedData['dtcm_fee'] ?? 0),
            'cleaning_fee' => (float) ($validatedData['cleaning_fee'] ?? 0),
            'agency_fee' => (float) ($validatedData['agency_fee'] ?? 0),
            'security_deposit' => (float) ($validatedData['security_deposit'] ?? 0),
        ];

        return [
            'rent_amount' => $baseRent,
            'management_fee_percent' => $managementFeePercent,
            'management_fee_amount' => $managementFeeAmount,
            'owner_rent_income' => $ownerRentIncome,
            'vat_included' => $vatIncluded,
            'vat_amount' => $vatAmount,
            'dtcm_fee' => $fees['dtcm_fee'],
            'cleaning_fee' => $fees['cleaning_fee'],
            'agency_fee' => $fees['agency_fee'],
            'security_deposit' => $fees['security_deposit'],
            'total_amount' => $baseRent + $vatAmount + array_sum($fees),
        ];
    }

    private function syncOwnerIncome(Booking $booking): void
    {
        $landlordId = $booking->property?->landlord_id;
        LandlordAccountEntry::where('reference', $booking->booking_reference)->delete();
        $this->recordOwnerIncome($booking);

        if ($landlordId) {
            LandlordAccountEntry::recalculateBalancesFor($landlordId);
        }
    }

    private function recordOwnerIncome(Booking $booking): void
    {
        if ($booking->owner_posting_basis === 'receipts') return;
        $property = $booking->property()->first();

        if (! $property || ! $property->landlord_id) {
            return;
        }

        $stayDuration = $booking->check_in?->format('d M Y') . ' to ' . $booking->check_out?->format('d M Y');
        $nights = $booking->nights;
        $durationText = trim($stayDuration) . ' (' . $nights . ' ' . Str::plural('night', $nights) . ')';
        $unitName = $property->name ?? 'Unit';

        LandlordAccountEntry::create([
            'landlord_id' => $property->landlord_id,
            'property_id' => $property->id,
            'entry_date' => $booking->check_in,
            'type' => 'rent_income',
            'direction' => 'credit',
            'amount' => $booking->rent_amount,
            'reference' => $booking->booking_reference,
            'description' => 'Booking rent income for ' . $unitName . ' - stay ' . $durationText,
        ]);

        if ((float) $booking->management_fee_amount > 0) {
            LandlordAccountEntry::create([
                'landlord_id' => $property->landlord_id,
                'property_id' => $property->id,
                'entry_date' => $booking->check_in,
                'type' => 'management_fee',
                'direction' => 'debit',
                'amount' => $booking->management_fee_amount,
                'reference' => $booking->booking_reference,
                'description' => 'Management fee ' . number_format((float) $booking->management_fee_percent, 2) . '% from ' . $unitName . ' rent only - stay ' . $durationText,
            ]);
        }

        LandlordAccountEntry::recalculateBalancesFor($property->landlord_id);
    }
}

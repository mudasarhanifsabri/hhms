<?php

namespace App\Http\Controllers\admin\bookings;

use App\Http\Controllers\Controller;
use App\Models\AccountingAccount;
use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
use App\Models\BookingTask;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use App\Support\MediaStorage;
use App\Support\BookingDeletion;
use App\Support\BookingInvoiceSchedule;
use App\Support\BookingPaymentSummary;
use App\Support\AppSettings;
use App\Support\PdfRenderer;
use App\Support\TtlockClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = $this->filteredBookings($request);
        $totalBookings = Booking::count();
        $paidInvoices = BookingInvoice::where('status', 'paid')->count();
        $partialInvoices = BookingInvoice::where('status', 'partial')->count();
        $unpaidInvoices = BookingInvoice::where('status', 'unpaid')->count();

        return view('admin.bookings.index', compact('bookings', 'totalBookings', 'paidInvoices', 'partialInvoices', 'unpaidInvoices'));
    }

    public function grid(Request $request)
    {
        $bookings = $this->filteredBookings($request);

        return view('admin.bookings.grid', compact('bookings'));
    }

    private function filteredBookings(Request $request, bool $paginate = true)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:200',
            'status' => 'nullable|in:confirmed,checked_in,checked_out',
            'invoice_status' => 'nullable|in:paid,unpaid,partial',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d'.($request->filled('from') ? '|after_or_equal:from' : ''),
            'per_page' => 'nullable|integer|in:10,12,25,50,100',
        ]);
        $query = Booking::with(['property.building', 'property.landlord', 'agent',
            'invoices' => fn ($query) => $query->withSum('payments', 'amount')->withCount('allPayments')]);
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
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['from'])) {
            $query->whereDate('check_in', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('check_in', '<=', $filters['to']);
        }

        $query->latest()->orderByDesc('id');

        return $paginate
            ? $query->paginate($filters['per_page'] ?? 12)->withQueryString()
            : $query->get();
    }

    public function exportExcel(Request $request)
    {
        $bookings = $this->filteredBookings($request, false);

        return response()->streamDownload(function () use ($bookings) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Booking Ref.', 'Guest', 'Email', 'Phone', 'Passport / ID', 'Owner', 'Building', 'Unit', 'Agent', 'Check In', 'Check Out', 'Nights', 'Booking Status', 'Payment Status', 'Rent', 'VAT', 'DTCM Fee', 'Cleaning Fee', 'Agency Fee', 'Security Deposit', 'Total (AED)', 'Received (AED)', 'Balance (AED)']);
            foreach ($bookings as $booking) {
                $paymentSummary = BookingPaymentSummary::for($booking);
                $row = [
                    $booking->booking_reference, $booking->guest_name, $booking->guest_email, $booking->guest_phone,
                    $booking->guest_passport_id_no, $booking->property?->landlord?->name,
                    $booking->property?->building?->building_name ?? $booking->property?->building?->name,
                    $booking->property?->name, $booking->agent?->name,
                    $booking->check_in?->format('Y-m-d'), $booking->check_out?->format('Y-m-d'), $booking->nights,
                    str($booking->status)->replace('_', ' ')->headline(), str($paymentSummary['status'])->headline(),
                    (float) $booking->rent_amount, (float) $booking->vat_amount, (float) $booking->dtcm_fee,
                    (float) $booking->cleaning_fee, (float) $booking->agency_fee, (float) $booking->security_deposit,
                    (float) $booking->total_amount, $paymentSummary['paid'], $paymentSummary['balance'],
                ];
                fputcsv($output, array_map(function ($value) {
                    if (! is_string($value)) {
                        return $value;
                    }
                    return preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value;
                }, $row));
            }
            fclose($output);
        }, 'booking-report-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request)
    {
        $bookings = $this->filteredBookings($request, false);
        $filters = $request->only(['search', 'status', 'invoice_status', 'from', 'to']);

        return PdfRenderer::downloadView('admin.bookings.pdf.list', compact('bookings', 'filters'), 'booking-report-'.now()->format('Y-m-d').'.pdf', ['format' => 'A4-L']);
    }

    public function create()
    {
        $properties = Property::with('building')->orderBy('name')->get();
        $agents = User::where('role', 'agent')->orderBy('name')->get();

        return view('admin.bookings.create', compact('properties', 'agents'));
    }

    public function commission(Request $request, Booking $booking)
    {
        $data = $request->validate(['agent_commission_percent' => 'required|numeric|between:0,100', 'reason' => 'required|string|min:5|max:500']);
        DB::transaction(function () use ($booking, $data) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if (! $booking->agent_id || BookingInvoicePayment::whereHas('invoice', fn ($q) => $q->where('booking_id', $booking->id))->exists()) {
                throw ValidationException::withMessages(['agent_commission_percent' => 'Assign an agent first. Commission is locked once payment history exists.']);
            }
            $before = $booking->agent_commission_percent ?? $booking->agent?->agent_commission ?? 0;
            $booking->update(['agent_commission_percent' => $data['agent_commission_percent']]);
            $booking->histories()->create(['title' => 'Agent Commission Updated', 'description' => $before.'% → '.$data['agent_commission_percent'].'% of agency fee by '.auth()->user()->name.'. '.$data['reason']]);
        });

        return back()->with('success', 'Booking agent commission updated.');
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateBooking($request);
        $this->ensurePropertyCanBeBooked($validatedData['property_id'], $validatedData['check_in'], $validatedData['check_out']);
        $periods = BookingInvoiceSchedule::periods($validatedData['check_in'], $validatedData['check_out']);
        if (! $periods) throw ValidationException::withMessages(['check_out' => 'Stay must be between 1 night and 3 years.']);
        $property = Property::findOrFail($validatedData['property_id']);
        $configuredDtcm = BookingInvoiceSchedule::dtcmRate($property);
        $enteredDtcm = (float) ($validatedData['dtcm_fee'] ?? 0);
        $dtcm = $enteredDtcm > 0 ? $enteredDtcm : ($configuredDtcm ?? 0);
        if (count($periods) > 3 && $dtcm <= 0) throw ValidationException::withMessages(['dtcm_fee' => 'Set the DTCM fee for this unit type in Settings, or enter the approved amount before creating a stay requiring renewal.']);
        $validatedData['dtcm_fee'] = $dtcm;
        $submittedRents = $request->input('period_rents', []);
        if ($submittedRents && (count($submittedRents) !== count($periods) || array_keys($submittedRents) !== range(0, count($periods) - 1))) {
            throw ValidationException::withMessages(['period_rents' => 'Review the invoice periods and enter rent for every period.']);
        }
        foreach ($periods as $index => $period) {
            $suggested = round((float) $validatedData['rent_amount'] * (count($periods) === 1 ? 1 : $period['nights'] / 30), 2);
            $rent = $submittedRents[$index] ?? $suggested;
            if (! is_numeric($rent) || (float) $rent < 0 || (float) $rent > 99999999) {
                throw ValidationException::withMessages(['period_rents' => 'Each invoice period needs a valid non-negative rent.']);
            }
            $periods[$index]['entered_rent'] = round((float) $rent, 2);
        }
        $validatedData['rent_amount'] = $periods[0]['entered_rent'];
        $amounts = $this->calculateAmounts($validatedData, $request);
        $bookings = DB::transaction(function () use ($validatedData, $amounts, $periods, $request, $dtcm) {
            $created = collect();
            $document = $this->uploadFile($request, 'guest_document', 'booking_documents');
            foreach (array_chunk($periods, 3, true) as $contractIndex => $contractPeriods) {
                $firstPeriod = reset($contractPeriods);
                $lastPeriod = end($contractPeriods);
                $booking = Booking::create([
                    ...$validatedData, ...$amounts,
                    'check_in' => $firstPeriod['from'], 'check_out' => $lastPeriod['to'],
                    'owner_posting_basis' => 'receipts', 'guest_document' => $document,
                    'renewed_from_booking_id' => $created->last()?->id,
                    'booking_reference' => $this->nextReference('BK'),
                    'invoice_number' => $contractIndex === 0 ? $this->nextReference('INV') : $this->nextInvoiceNumber('INV-REN'),
                    'cleaning_fee' => $contractIndex === 0 ? $amounts['cleaning_fee'] : 0,
                    'agency_fee' => $contractIndex === 0 ? $amounts['agency_fee'] : 0,
                    'security_deposit' => $contractIndex === 0 ? $amounts['security_deposit'] : 0,
                    'status' => 'confirmed', 'invoice_status' => 'unpaid',
                ]);
                $booking->histories()->create([
                    'title' => $contractIndex === 0 ? 'Booking Created' : 'Scheduled Renewal Created',
                    'description' => count($contractPeriods).' period invoice(s) generated for '.$booking->guest_name.'.',
                ]);
                $totalRent = $totalVat = $totalAmount = $totalDtcm = 0;
                foreach ($contractPeriods as $index => $period) {
                    $enteredRent = $period['entered_rent'];
                    $rentVat = $request->boolean('vat_included') ? round($enteredRent - $enteredRent / 1.05, 2) : round($enteredRent * .05, 2);
                    $baseRent = $request->boolean('vat_included') ? round($enteredRent - $rentVat, 2) : $enteredRent;
                    $firstOfContract = $index % 3 === 0;
                    $fees = ['DTCM Fee' => $firstOfContract ? $dtcm : 0,
                        'Cleaning Fee' => $index === 0 ? (float) $amounts['cleaning_fee'] : 0,
                        'Agency Fee' => $index === 0 ? (float) $amounts['agency_fee'] : 0,
                        'Security Deposit' => $index === 0 ? (float) $amounts['security_deposit'] : 0];
                    $feeVat = $index === 0 ? round(((float) $amounts['cleaning_fee'] + (float) $amounts['agency_fee']) * .05, 2) : 0;
                    $invoice = $this->createBookingInvoice($booking, $firstOfContract ? ($contractIndex === 0 ? 'original' : 'renewal') : 'extension', [
                        'invoice_number' => $firstOfContract ? $booking->invoice_number : null,
                        'period_from' => $period['from'], 'period_to' => $period['to'], 'due_date' => $period['due_date'],
                        'rent_amount' => $baseRent, 'vat_amount' => $rentVat + $feeVat, 'vat_included' => $request->boolean('vat_included'),
                        'fees' => $fees, 'notes' => 'Scheduled period '.($index + 1).' of '.count($periods),
                    ]);
                    $totalRent += $baseRent; $totalVat += (float) $invoice->vat_amount;
                    $totalAmount += (float) $invoice->total_amount; $totalDtcm += (float) $fees['DTCM Fee'];
                }
                $managementFee = round($totalRent * (float) $booking->management_fee_percent / 100, 2);
                $booking->forceFill(['rent_amount' => $totalRent, 'vat_amount' => $totalVat, 'dtcm_fee' => $totalDtcm,
                    'total_amount' => $totalAmount, 'management_fee_amount' => $managementFee,
                    'owner_rent_income' => $totalRent - $managementFee])->save();
                $created->push($booking);
            }
            return $created;
        });

        $temporaryPassword = null;
        foreach ($bookings as $booking) {
            try {
                \App\Support\BookingTenantProfile::sync($booking, $temporaryPassword);
            } catch (Throwable $exception) {
                Log::error('Booking created but tenant profile synchronization failed.', [
                    'booking_id' => $booking->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
        $this->markPropertyStatus($bookings->first(), 'booked');
        try {
            \App\Support\BookingGuestCommunications::created($bookings, $temporaryPassword);
        } catch (Throwable $exception) {
            Log::error('Booking created but guest communications failed.', [
                'booking_id' => $bookings->first()?->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('admin.booking.show', $bookings->first()->id)
            ->with('success', count($periods).' period invoices across '.$bookings->count().' linked contract(s) created successfully.');
    }

    public function edit(Booking $booking)
    {
        $booking->load(['property', 'agent', 'invoices.allPayments']);
        $properties = Property::with('building')->orderBy('name')->get();
        $agents = User::where('role', 'agent')->orderBy('name')->get();

        return view('admin.bookings.edit', compact('booking', 'properties', 'agents'));
    }

    public function attachment(Booking $booking)
    {
        abort_if(blank($booking->guest_document), 404);

        $path = trim((string) $booking->guest_document, '/');
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return redirect()->away($path);
        }

        // Support files from the original public-folder implementation as well
        // as current files stored through the configured media disk.
        if (MediaStorage::disk() === 'public' && is_file(public_path($path))) {
            return response()->file(public_path($path));
        }

        $disk = Storage::disk(MediaStorage::disk());
        $storagePath = MediaStorage::path($path);
        abort_unless($disk->exists($storagePath), 404, 'Booking attachment not found.');

        return $disk->response($storagePath, basename($path), [
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(Request $request, Booking $booking)
    {
        if ($booking->invoices()->count() > 1) {
            throw ValidationException::withMessages(['invoice' => 'This booking has multiple scheduled invoices. Edit each unpaid invoice separately; changing stay dates or total booking charges here could invalidate paid periods.']);
        }
        if ($booking->invoices()->exists()) {
            $validatedData = $this->validateBooking($request);
            $reason = $request->validate(['reason' => 'required|string|min:5|max:1000'])['reason'];
            $this->ensurePropertyCanBeBooked($validatedData['property_id'], $validatedData['check_in'], $validatedData['check_out'], $booking->id);
            $amounts = $this->calculateAmounts($validatedData, $request);
            $primaryInvoice = $booking->invoices()->where('invoice_type', 'original')->first() ?? $booking->invoices()->oldest('issue_date')->first();
            if ($primaryInvoice?->vat_scope !== 'rent_cleaning_agency') {
                $legacyFeeVat = round(((float)$amounts['cleaning_fee'] + (float)$amounts['agency_fee']) * 0.05, 2);
                $amounts['vat_amount'] -= $legacyFeeVat;
                $amounts['total_amount'] -= $legacyFeeVat;
            }
            DB::transaction(function () use ($request, $booking, $validatedData, $amounts, $reason) {
                $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
                $invoice = $booking->invoices()->where('invoice_type', 'original')->lockForUpdate()->first()
                    ?? $booking->invoices()->oldest('issue_date')->lockForUpdate()->firstOrFail();
                $fees = ['DTCM Fee' => $amounts['dtcm_fee'], 'Cleaning Fee' => $amounts['cleaning_fee'],
                    'Agency Fee' => $amounts['agency_fee'], 'Security Deposit' => $amounts['security_deposit']];
                $financialChanged = abs((float)$invoice->rent_amount - (float)$amounts['rent_amount']) > .009
                    || abs((float)$invoice->vat_amount - (float)$amounts['vat_amount']) > .009
                    || abs((float)$invoice->total_amount - (float)$amounts['total_amount']) > .009
                    || collect($fees)->contains(fn ($amount, $label) => abs((float)($invoice->fees[$label] ?? 0) - (float)$amount) > .009);
                if ($financialChanged && ($invoice->allPayments()->whereNull('reversed_at')->exists()
                    || \App\Models\BookingDepositEntry::where('booking_invoice_id', $invoice->id)->exists())) {
                    throw ValidationException::withMessages(['invoice' => 'Charges cannot change after payment or deposit activity. Reverse the affected payment/deposit first, then edit the booking.']);
                }
                $before = $booking->only(['property_id','agent_id','guest_name','guest_email','guest_phone','guest_passport_id_no',
                    'check_in','check_in_time','check_out','check_out_time','rent_amount','vat_amount','dtcm_fee','cleaning_fee','agency_fee','security_deposit','total_amount','notes']);
                $payload = [...$validatedData, ...$amounts];
                if ($request->hasFile('guest_document')) {
                    $payload['guest_document'] = $this->uploadFile($request, 'guest_document', 'booking_documents');
                } else {
                    unset($payload['guest_document']);
                }
                $booking->update($payload);
                if ($financialChanged) {
                    $invoice->update(['period_from' => $booking->check_in, 'period_to' => $booking->check_out,
                        'rent_amount' => $amounts['rent_amount'], 'vat_included' => $amounts['vat_included'],
                        'vat_amount' => $amounts['vat_amount'], 'fees' => $fees, 'total_amount' => $amounts['total_amount']]);
                }
                $booking->histories()->create(['title' => 'Booking Corrected', 'description' => 'Full booking edited by '.auth()->user()->name.'. Reason: '.$reason.' | Before: '.json_encode($before).' | After: '.json_encode($booking->only(array_keys($before)))]);
            });
            \App\Support\BookingTenantProfile::sync($booking->fresh());

            return redirect()->route('admin.booking.show', $booking)->with('success', 'Complete booking details updated successfully.');
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
        \App\Support\BookingTenantProfile::sync($booking->fresh());
        $this->markPropertyStatus($booking, 'booked');

        return redirect()->route('admin.booking.show', $booking->id)
            ->with('success', 'Booking updated successfully.');
    }

    public function confirmDelete(Booking $booking)
    {
        $booking->loadCount(['invoices', 'tasks', 'inspections']);

        return view('admin.bookings.delete', compact('booking'));
    }

    public function destroy(Request $request, Booking $booking, BookingDeletion $deletion)
    {
        $data = $request->validate([
            'booking_reference' => ['required', 'string'],
            'current_password' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        if ($data['booking_reference'] !== $booking->booking_reference) {
            throw ValidationException::withMessages(['booking_reference' => 'Type the exact booking number to confirm deletion.']);
        }
        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'The password is incorrect.']);
        }
        $deletion->delete($booking, $data['reason']);

        return redirect()->route('admin.booking.index')
            ->with('success', 'Booking and its linked financial records were deleted.');
    }

    public function extend(Request $request, Booking $booking)
    {
        $validatedData = $request->validate([
            'check_out' => 'required|date|after:'.$booking->check_out?->toDateString(),
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

        $createdInvoice = DB::transaction(function () use ($booking, $validatedData, $additionalRent, $oldCheckOut) {
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
                'notes' => 'Extension invoice for booking '.$booking->booking_reference,
            ]);

            $booking->histories()->create([
                'title' => 'Booking Extended',
                'description' => 'Extended until '.$booking->check_out?->format('d M Y').'. Separate invoice '.$invoice->invoice_number.' created for AED '.number_format((float) $invoice->total_amount, 2).'.',
            ]);
            $this->recordOwnerIncomeForInvoice($invoice);
            return $invoice;
        });

        \App\Support\BookingGuestCommunications::invoiceCreated($createdInvoice);

        return back()->with('success', 'Booking extended. A separate extension invoice and payment balance were created.');
    }

    public function renew(Request $request, Booking $booking)
    {
        $validatedData = $request->validate([
            'check_in' => 'required|date|after_or_equal:'.$booking->check_out?->toDateString(),
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
            'agent_commission_percent' => $booking->agent_commission_percent,
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
            'description' => 'Renewed from booking '.$booking->booking_reference.'.'.(! empty($validatedData['notes']) ? ' Notes: '.$validatedData['notes'] : ''),
        ]);
        $booking->histories()->create([
            'title' => 'Renewal Created',
            'description' => 'Renewal booking '.$newBooking->booking_reference.' was created.',
        ]);

        $invoice = $this->createBookingInvoice($newBooking, 'renewal', [
            'vat_amount' => (float) $newBooking->vat_amount,
            'notes' => 'Renewal invoice from booking '.$booking->booking_reference,
        ]);
        $this->recordOwnerIncomeForInvoice($invoice);
        $this->markPropertyStatus($newBooking, 'booked');
        \App\Support\BookingTenantProfile::sync($newBooking);
        \App\Support\BookingGuestCommunications::invoiceCreated($invoice);

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
            'lockAccesses',
            'renewedFrom', 'renewals',
        ]);

        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();
        $depositTotals = \App\Support\DepositWallet::totals($booking);

        return view('admin.bookings.show', compact('booking', 'bankAccounts', 'depositTotals'));
    }

    public function recordInvoicePayment(Request $request, BookingInvoice $invoice)
    {
        $data = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|decimal:0,2|min:0.01',
            'deposit_submission_id' => 'nullable|uuid',
            'payment_method' => 'required|string|max:100',
            'bank_account_id' => 'required|exists:bank_accounts,id,is_active,1',
            'reference' => 'nullable|string|max:150',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string|max:2000',
        ]);

        $invoice->load('payments', 'booking.property');
        DB::transaction(function () use ($request, $invoice, $data) {
            Booking::whereKey($invoice->booking_id)->lockForUpdate()->firstOrFail();
            $invoice = BookingInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $paidBefore = $invoice->paid_amount;
            if (\App\Support\DepositWallet::cents($data['amount']) > \App\Support\DepositWallet::cents($invoice->balance_due)) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the outstanding invoice balance of AED '.number_format($invoice->balance_due, 2).'.']);
            }
            $allocation = \App\Support\InvoiceSettlement::allocation($invoice, (float) $data['amount']);
            $data['rent_amount'] = $allocation['rent'];
            $data['deposit_amount'] = $allocation['deposit'];
            $entry = AccountingEntry::create([
                'entry_no' => $this->nextAccountingEntryNumber(),
                'entry_date' => $data['payment_date'],
                'type' => 'income', 'category' => 'guest_receipt',
                'accounting_account_id' => AccountingAccount::where('code', '2096')->firstOrFail()->id,
                'description' => 'Guest payment for '.$invoice->invoice_number,
                'property_id' => $invoice->booking?->property_id,
                'landlord_id' => $invoice->booking?->property?->landlord_id,
                'booking_id' => $invoice->booking_id,
                'paid_from_account_id' => $data['bank_account_id'] ?? null,
                'debit' => 0, 'credit' => $data['amount'],
                'vat_rate' => $invoice->vat_rate, 'vat_amount' => $allocation['vat'],
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
                'allocation' => $allocation,
            ]);

            if ((float) ($data['deposit_amount'] ?? 0) > 0) {
                \App\Support\DepositWallet::allocate($invoice->booking, $payment, (float) $data['deposit_amount'], $data['deposit_submission_id'] ?? 'payment:'.$payment->id);
            }

            $paid = $paidBefore + (float) $data['amount'];
            \App\Support\OwnerReceiptPosting::post($payment);
            \App\Support\InvoiceSettlement::post($payment);
            $invoice->booking->histories()->create(['title' => 'Payment Recorded', 'description' => 'Payment '.$payment->id.' for '.$invoice->invoice_number.': AED '.number_format((float) $payment->amount, 2).'; rent portion '.($payment->rent_amount ?? 'legacy').'; recorded by '.auth()->user()->name.'.']);
            $invoice->update(['status' => $paid >= (float) $invoice->total_amount ? 'paid' : 'partial']);
            BookingPaymentSummary::sync($invoice->booking);
            if ($data['bank_account_id'] ?? null) {
                $account = BankAccount::whereKey($data['bank_account_id'])->lockForUpdate()->first();
                $account?->forceFill(['current_balance' => (float) $account->opening_balance + (float) $account->entries()->whereIn('approval_status', ['posted', 'approved', 'paid'])->selectRaw('COALESCE(SUM(credit - debit),0) as movement')->value('movement')])->save();
            }
        });

        \App\Support\BookingGuestCommunications::paymentRecorded($invoice, (float) $data['amount']);

        return back()->with('success', 'Payment recorded against '.$invoice->invoice_number.'.');
    }

    public function paymentReceipt(BookingInvoice $invoice)
    {
        $invoice->load(['booking.property.building', 'payments.bankAccount']);
        abort_if($invoice->payments->isEmpty(), 422, 'No itemised payment records exist for this invoice. A receipt cannot be generated from an invoice status alone.');

        return PdfRenderer::downloadView('admin.bookings.pdf.payment-receipt', compact('invoice'), $invoice->invoice_number.'-receipt.pdf');
    }

    public function recordCombinedPayment(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|decimal:0,2|min:0.01',
            'payment_method' => 'required|string|max:100',
            'bank_account_id' => 'required|exists:bank_accounts,id,is_active,1',
            'reference' => 'required|string|max:150',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string|max:2000',
            'submission_id' => 'required|uuid',
        ]);
        if (DB::table('booking_payment_batches')->where('id', $data['submission_id'])->exists()) {
            return back()->with('success', 'This combined payment was already recorded.');
        }
        $receiptPath = $this->uploadFile($request, 'receipt', 'booking_payment_proofs');

        $notifiedPayments = [];
        DB::transaction(function () use ($booking, $data, $receiptPath, &$notifiedPayments) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $invoices = BookingInvoice::where('booking_id', $booking->id)->whereIn('status', ['unpaid', 'partial'])
                ->orderBy('issue_date')->orderBy('created_at')->lockForUpdate()->get();
            $outstanding = round($invoices->sum(fn ($invoice) => $invoice->balance_due), 2);
            if ($invoices->count() < 2) {
                throw ValidationException::withMessages(['amount' => 'Combined payment requires at least two outstanding invoices.']);
            }
            if (\App\Support\DepositWallet::cents($data['amount']) <= \App\Support\DepositWallet::cents($invoices->first()->balance_due)) {
                throw ValidationException::withMessages(['amount' => 'This amount reaches only the oldest invoice. Use its Record Payment button, or enter an amount that also reaches the next invoice.']);
            }
            if (\App\Support\DepositWallet::cents($data['amount']) > \App\Support\DepositWallet::cents($outstanding)) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the combined outstanding balance of AED '.number_format($outstanding, 2).'.']);
            }

            $batchId = $data['submission_id'];
            $entry = AccountingEntry::create([
                'entry_no' => $this->nextAccountingEntryNumber(), 'entry_date' => $data['payment_date'],
                'type' => 'income', 'category' => 'guest_receipt',
                'accounting_account_id' => AccountingAccount::where('code', '2096')->firstOrFail()->id,
                'description' => 'Combined guest payment for '.$booking->booking_reference,
                'property_id' => $booking->property_id, 'landlord_id' => $booking->property?->landlord_id,
                'booking_id' => $booking->id, 'paid_from_account_id' => $data['bank_account_id'],
                'debit' => 0, 'credit' => $data['amount'], 'vat_rate' => 0, 'vat_amount' => 0,
                'net_amount' => $data['amount'], 'gross_amount' => $data['amount'],
                'payment_method' => $data['payment_method'], 'transaction_reference' => $data['reference'],
                'status' => 'posted', 'approval_status' => 'posted', 'created_by' => auth()->id(),
            ]);
            DB::table('booking_payment_batches')->insert([
                'id' => $batchId, 'booking_id' => $booking->id, 'accounting_entry_id' => $entry->id,
                'amount' => $data['amount'], 'reference' => $data['reference'], 'created_by' => auth()->id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $remaining = round((float) $data['amount'], 2);
            $summary = [];
            foreach ($invoices as $invoice) {
                if ($remaining <= 0) {
                    break;
                }
                $amount = min($remaining, $invoice->balance_due);
                $allocation = \App\Support\InvoiceSettlement::allocation($invoice, $amount);
                $payment = BookingInvoicePayment::create([
                    'booking_invoice_id' => $invoice->id, 'payment_batch_id' => $batchId,
                    'payment_date' => $data['payment_date'], 'amount' => $amount,
                    'payment_method' => $data['payment_method'], 'bank_account_id' => $data['bank_account_id'],
                    'reference' => $data['reference'], 'receipt_path' => $receiptPath, 'notes' => $data['notes'] ?? null,
                    'accounting_entry_id' => $entry->id, 'created_by' => auth()->id(),
                    'rent_amount' => $booking->owner_posting_basis === 'receipts' ? $allocation['rent'] : null,
                    'allocation' => $allocation,
                ]);
                if ($allocation['deposit'] > 0) {
                    \App\Support\DepositWallet::allocate($booking, $payment, $allocation['deposit'], 'batch:'.$batchId.':'.$invoice->id);
                }
                \App\Support\OwnerReceiptPosting::post($payment);
                \App\Support\InvoiceSettlement::post($payment);
                $invoice->update(['status' => $invoice->fresh()->balance_due <= 0 ? 'paid' : 'partial']);
                $notifiedPayments[] = [$invoice->id, (float) $amount];
                $summary[] = $invoice->invoice_number.' AED '.number_format($amount, 2);
                $remaining = round($remaining - $amount, 2);
            }
            BookingPaymentSummary::sync($booking);
            $booking->histories()->create(['title' => 'Combined Payment Recorded', 'description' => 'Transfer '.$data['reference'].' allocated: '.implode('; ', $summary).'.']);
            $account = BankAccount::whereKey($data['bank_account_id'])->lockForUpdate()->first();
            $account?->forceFill(['current_balance' => (float) $account->opening_balance + (float) $account->entries()->whereIn('approval_status', ['posted', 'approved', 'paid'])->selectRaw('COALESCE(SUM(credit - debit),0) as movement')->value('movement')])->save();
        });

        foreach ($notifiedPayments as [$invoiceId, $amount]) {
            \App\Support\BookingGuestCommunications::paymentRecorded(BookingInvoice::findOrFail($invoiceId), $amount);
        }

        return back()->with('success', 'Combined payment recorded and allocated across outstanding invoices.');
    }

    public function history(Booking $booking)
    {
        $booking->load(['property', 'agent', 'histories', 'invoices.allPayments.bankAccount']);
        $ownerReconciliation = \App\Support\LegacyOwnerReconciliation::inspect($booking);

        return view('admin.bookings.history', compact('booking', 'ownerReconciliation'));
    }

    public function attachPaymentProof(Request $request, Booking $booking)
    {
        return back()->withErrors(['payment' => 'Use Record Payment on the invoice to enter the actual amount, account, rent allocation and deposit portion. Uploading proof alone cannot mark an invoice paid.']);
    }

    private function nextAccountingEntryNumber(): string
    {
        do {
            $number = 'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        } while (AccountingEntry::where('entry_no', $number)->exists());

        return $number;
    }

    public function checkIn(Request $request, Booking $booking)
    {
        if ($booking->status !== 'confirmed' || $booking->checked_in_at) {
            return back()->withErrors(['workflow' => 'Only a confirmed booking that has not been checked in can be checked in.']);
        }
        if (! \App\Support\InvoiceSettlement::firstPeriodPaid($booking)) {
            return back()->withErrors(['workflow' => 'The first stay-period invoice must be fully paid before check-in. Future invoices may remain unpaid.']);
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

        $revokeFailed = false;
        foreach ($booking->lockAccesses()->whereNull('revoked_at')->with('smartlock')->get() as $access) {
            try {
                app(TtlockClient::class)->deletePasscode((int) $access->smartlock->remote_id, (int) $access->remote_passcode_id);
                $access->update(['revoked_at' => now()]);
            } catch (Throwable $exception) {
                report($exception);
                $revokeFailed = true;
            }
        }

        return back()->with($revokeFailed ? 'error' : 'success', $revokeFailed
            ? 'Check out completed, but TTLock could not confirm every code was revoked. Verify the lock and revoke remaining codes manually.'
            : 'Check out completed, tasks created, and guest door access revoked.');
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
        $invoice = $booking->invoices()->orderBy('period_from')->firstOrFail();

        return redirect()->route('admin.accounting.booking-invoices.pdf', $invoice);
    }

    public function confirmation(Booking $booking)
    {
        \App\Support\InvoiceSettlement::assertBookingPaid($booking);
        $booking->load(['property.building', 'agent']);

        return PdfRenderer::downloadView('admin.bookings.pdf.confirmation', compact('booking'), $booking->booking_reference.'-confirmation.pdf');
    }

    public function completePack(Booking $booking)
    {
        $booking->load(['property.building', 'agent', 'invoices.payments.bankAccount']);
        \App\Support\InvoiceSettlement::assertBookingPaid($booking);

        return PdfRenderer::downloadView('admin.bookings.pdf.complete-pack', compact('booking'), $booking->booking_reference.'-complete-booking-pack.pdf');
    }

    public function invoiceConfirmation(BookingInvoice $invoice)
    {
        \App\Support\InvoiceSettlement::assertPaid($invoice);
        $invoice->load('booking.property.building', 'booking.agent');
        $booking = $invoice->booking;

        return PdfRenderer::downloadView(
            'admin.bookings.pdf.confirmation',
            compact('booking', 'invoice'),
            $invoice->invoice_number.'-booking-confirmation.pdf'
        );
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
        $booking->property?->update(['status' => match ($status) {
            'booked' => 'rented', 'under_cleaning' => 'vacant', default => $status
        }]);
    }

    private function nextTaskNumber(): string
    {
        do {
            $number = 'TSK-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));
        } while (BookingTask::where('task_number', $number)->exists());

        return $number;
    }

    private function nextReference(string $prefix): string
    {
        do {
            $reference = $prefix.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        } while (Booking::where($prefix === 'INV' ? 'invoice_number' : 'booking_reference', $reference)->exists());

        return $reference;
    }

    private function nextInvoiceNumber(string $prefix = 'INV'): string
    {
        do {
            $reference = $prefix.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
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
        $vatScope = $override['vat_scope'] ?? 'rent_cleaning_agency';
        $taxableFeeVat = $vatScope === 'rent_cleaning_agency'
            ? round(((float) ($fees['Cleaning Fee'] ?? 0) + (float) ($fees['Agency Fee'] ?? 0)) * ($vatRate / 100), 2)
            : 0;

        if ($type === 'original') {
            $vatAmount = (float) $booking->vat_amount;
        } elseif (! array_key_exists('vat_amount', $override)) {
            $vatAmount += $taxableFeeVat;
        }

        return BookingInvoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => $override['invoice_number'] ?? ($type === 'original' ? $booking->invoice_number : $this->nextInvoiceNumber($type === 'extension' ? 'INV-EXT' : 'INV-REN')),
            'invoice_type' => $type,
            'issue_date' => now()->toDateString(),
            'period_from' => $override['period_from'] ?? $booking->check_in,
            'period_to' => $override['period_to'] ?? $booking->check_out,
            'due_date' => $override['due_date'] ?? null,
            'rent_amount' => $rentAmount,
            'vat_rate' => $vatRate,
            'vat_included' => (bool) ($override['vat_included'] ?? ($type === 'extension' ? false : $booking->vat_included)),
            'vat_amount' => $vatAmount,
            'vat_scope' => $vatScope,
            'fees' => $fees,
            'total_amount' => $rentAmount + $vatAmount + $feeTotal,
            'status' => 'unpaid',
            'notes' => $override['notes'] ?? null,
        ]);
    }

    private function recordOwnerIncomeForInvoice(BookingInvoice $invoice): void
    {
        $booking = $invoice->booking()->with('property')->first();
        if ($booking?->owner_posting_basis === 'receipts') {
            return;
        }
        $property = $booking?->property;
        if (! $property?->landlord_id) {
            return;
        }

        $reference = $invoice->invoice_number;
        $description = $invoice->type_label.' rent for '.($property->name ?? 'Unit').' - '.$invoice->period_from?->format('d M Y').' to '.$invoice->period_to?->format('d M Y');
        LandlordAccountEntry::firstOrCreate(['landlord_id' => $property->landlord_id, 'reference' => $reference, 'type' => 'rent_income'], [
            'property_id' => $property->id, 'entry_date' => $invoice->period_from ?? now(), 'direction' => 'credit',
            'amount' => $invoice->rent_amount, 'description' => $description,
        ]);

        $rate = (float) ($property->management_fee_percent ?? 0);
        $fee = round((float) $invoice->rent_amount * $rate / 100, 2);
        if ($fee > 0) {
            LandlordAccountEntry::firstOrCreate(['landlord_id' => $property->landlord_id, 'reference' => $reference, 'type' => 'management_fee'], [
                'property_id' => $property->id, 'entry_date' => $invoice->period_from ?? now(), 'direction' => 'debit',
                'amount' => $fee, 'description' => 'Management fee '.number_format($rate, 2).'% on '.$description,
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
        $rentVatAmount = $vatIncluded ? round($rentAmount - ($rentAmount / 1.05), 2) : round($rentAmount * 0.05, 2);
        $baseRent = $vatIncluded ? round($rentAmount - $rentVatAmount, 2) : $rentAmount;
        $managementFeePercent = (float) ($property->management_fee_percent ?? 0);
        $managementFeeAmount = round($baseRent * ($managementFeePercent / 100), 2);
        $ownerRentIncome = round($baseRent - $managementFeeAmount, 2);
        $fees = [
            'dtcm_fee' => (float) ($validatedData['dtcm_fee'] ?? 0),
            'cleaning_fee' => (float) ($validatedData['cleaning_fee'] ?? 0),
            'agency_fee' => (float) ($validatedData['agency_fee'] ?? 0),
            'security_deposit' => (float) ($validatedData['security_deposit'] ?? 0),
        ];
        $taxableFeeVat = round(($fees['cleaning_fee'] + $fees['agency_fee']) * 0.05, 2);
        $vatAmount = $rentVatAmount + $taxableFeeVat;

        return [
            'rent_amount' => $baseRent,
            'agent_commission_percent' => ! empty($validatedData['agent_id']) ? ($validatedData['agent_commission_percent'] ?? (float) User::where('role', 'agent')->whereKey($validatedData['agent_id'])->value('agent_commission')) : 0,
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
        if ($booking->owner_posting_basis === 'receipts') {
            return;
        }
        $property = $booking->property()->first();

        if (! $property || ! $property->landlord_id) {
            return;
        }

        $stayDuration = $booking->check_in?->format('d M Y').' to '.$booking->check_out?->format('d M Y');
        $nights = $booking->nights;
        $durationText = trim($stayDuration).' ('.$nights.' '.Str::plural('night', $nights).')';
        $unitName = $property->name ?? 'Unit';

        LandlordAccountEntry::create([
            'landlord_id' => $property->landlord_id,
            'property_id' => $property->id,
            'entry_date' => $booking->check_in,
            'type' => 'rent_income',
            'direction' => 'credit',
            'amount' => $booking->rent_amount,
            'reference' => $booking->booking_reference,
            'description' => 'Booking rent income for '.$unitName.' - stay '.$durationText,
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
                'description' => 'Management fee '.number_format((float) $booking->management_fee_percent, 2).'% from '.$unitName.' rent only - stay '.$durationText,
            ]);
        }

        LandlordAccountEntry::recalculateBalancesFor($property->landlord_id);
    }
}

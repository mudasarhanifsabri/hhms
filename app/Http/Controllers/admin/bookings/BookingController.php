<?php

namespace App\Http\Controllers\admin\bookings;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\BookingInvoice;
use App\Models\BookingTask;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use App\Support\MediaStorage;
use App\Support\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['property.building', 'agent'])->latest()->paginate($request->input('per_page', 10));
        $totalBookings = Booking::count();
        $paidInvoices = Booking::where('invoice_status', 'paid')->count();
        $unpaidInvoices = Booking::where('invoice_status', 'unpaid')->count();

        return view('admin.bookings.index', compact('bookings', 'totalBookings', 'paidInvoices', 'unpaidInvoices'));
    }

    public function grid(Request $request)
    {
        $bookings = Booking::with(['property.building', 'agent'])->latest()->paginate($request->input('per_page', 12));

        return view('admin.bookings.grid', compact('bookings'));
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
        $properties = Property::with('building')->orderBy('name')->get();
        $agents = User::where('role', 'agent')->orderBy('name')->get();

        return view('admin.bookings.edit', compact('booking', 'properties', 'agents'));
    }

    public function update(Request $request, Booking $booking)
    {
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
            'extension_rent_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        $additionalRent = (float) ($validatedData['extension_rent_amount'] ?? 0);
        $oldCheckOut = $booking->check_out?->copy();
        $this->ensurePropertyCanBeBooked($booking->property_id, $booking->check_in?->toDateString(), $validatedData['check_out'], $booking->id);
        $booking->check_out = $validatedData['check_out'];
        $booking->check_out_time = $validatedData['check_out_time'] ?? $booking->check_out_time;

        if ($additionalRent > 0) {
            $grossRent = (float) $booking->rent_amount + $additionalRent;
            $requestForAmounts = new Request([
                'vat_included' => $booking->vat_included,
            ]);
            $amounts = $this->calculateAmounts([
                'property_id' => $booking->property_id,
                'rent_amount' => $grossRent,
                'dtcm_fee' => $booking->dtcm_fee,
                'cleaning_fee' => $booking->cleaning_fee,
                'agency_fee' => $booking->agency_fee,
                'security_deposit' => $booking->security_deposit,
            ], $requestForAmounts);
            $booking->fill($amounts);
        }

        $booking->save();
        $booking->histories()->create([
            'title' => 'Booking Extended',
            'description' => 'Extended until ' . $booking->check_out?->format('d M Y') . ($additionalRent > 0 ? ' with additional rent ' . number_format($additionalRent, 2) . ' AED.' : '.') . (! empty($validatedData['notes']) ? ' Notes: ' . $validatedData['notes'] : ''),
        ]);

        $this->syncOwnerIncome($booking);

        if ($additionalRent > 0) {
            $this->createBookingInvoice($booking, 'extension', [
                'period_from' => $oldCheckOut?->copy()->addDay() ?? $booking->check_in,
                'period_to' => $booking->check_out,
                'rent_amount' => $additionalRent,
                'fees' => [],
                'notes' => 'Extension invoice for booking ' . $booking->booking_reference,
            ]);
        }

        return back()->with('success', 'Booking extended successfully.');
    }

    public function renew(Request $request, Booking $booking)
    {
        $validatedData = $request->validate([
            'check_in' => 'required|date|after_or_equal:' . $booking->check_out?->toDateString(),
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out' => 'required|date|after:check_in',
            'check_out_time' => 'nullable|date_format:H:i',
            'rent_amount' => 'required|numeric|min:0',
            'dtcm_fee' => 'nullable|numeric|min:0',
            'cleaning_fee' => 'nullable|numeric|min:0',
            'agency_fee' => 'nullable|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

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

        $this->recordOwnerIncome($newBooking);
        $this->createBookingInvoice($newBooking, 'renewal', [
            'notes' => 'Renewal invoice from booking ' . $booking->booking_reference,
        ]);
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
        ]);

        return view('admin.bookings.show', compact('booking'));
    }

    public function history(Booking $booking)
    {
        $booking->load(['property', 'agent', 'histories']);

        return view('admin.bookings.history', compact('booking'));
    }

    public function attachPaymentProof(Request $request, Booking $booking)
    {
        $request->validate([
            'payment_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $booking->update([
            'payment_proof' => $this->uploadFile($request, 'payment_proof', 'booking_payment_proofs'),
            'invoice_status' => 'paid',
            'status' => 'confirmed',
        ]);

        $booking->histories()->create([
            'title' => 'Invoice Paid',
            'description' => 'Payment proof was attached to invoice ' . $booking->invoice_number . '.',
        ]);

        return back()->with('success', 'Payment proof attached and invoice marked paid.');
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

        return back()->with('success', 'Check out completed and tasks created.');
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
            if ($booking->tasks()->where('type', $type)->exists()) {
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
        $booking->property?->update(['status' => $status]);
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
        $vatRate = 5.0;
        $vatAmount = $booking->vat_included && $type !== 'original'
            ? round($rentAmount - ($rentAmount / 1.05), 2)
            : round($rentAmount * ($vatRate / 100), 2);
        $rentAmount = $booking->vat_included && $type !== 'original'
            ? round($rentAmount - $vatAmount, 2)
            : $rentAmount;
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
            'vat_amount' => $vatAmount,
            'fees' => $fees,
            'total_amount' => $rentAmount + $vatAmount + $feeTotal,
            'status' => 'unpaid',
            'notes' => $override['notes'] ?? null,
        ]);
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

<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;

class GuestPortalController extends Controller
{
    public function show(string $reference)
    {
        $booking = Booking::with(['property.building', 'agent'])
            ->where('booking_reference', $reference)
            ->orWhere('invoice_number', $reference)
            ->firstOrFail();

        return view('guest.booking', compact('booking'));
    }

    public function invoice(string $reference)
    {
        $booking = $this->findBooking($reference);
        $pdf = Pdf::loadView('admin.bookings.pdf.invoice', compact('booking'));

        return $pdf->download($booking->invoice_number . '.pdf');
    }

    public function confirmation(string $reference)
    {
        $booking = $this->findBooking($reference);
        $pdf = Pdf::loadView('admin.bookings.pdf.confirmation', compact('booking'));

        return $pdf->download($booking->booking_reference . '-confirmation.pdf');
    }

    private function findBooking(string $reference): Booking
    {
        return Booking::with(['property.building', 'agent'])
            ->where('booking_reference', $reference)
            ->orWhere('invoice_number', $reference)
            ->firstOrFail();
    }
}

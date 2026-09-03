<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\PdfRenderer;

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

        return PdfRenderer::downloadView('admin.bookings.pdf.invoice', compact('booking'), $booking->invoice_number . '.pdf');
    }

    public function confirmation(string $reference)
    {
        $booking = $this->findBooking($reference);
        \App\Support\InvoiceSettlement::assertBookingPaid($booking);

        return PdfRenderer::downloadView('admin.bookings.pdf.confirmation', compact('booking'), $booking->booking_reference . '-confirmation.pdf');
    }

    private function findBooking(string $reference): Booking
    {
        return Booking::with(['property.building', 'agent'])
            ->where('booking_reference', $reference)
            ->orWhere('invoice_number', $reference)
            ->firstOrFail();
    }
}

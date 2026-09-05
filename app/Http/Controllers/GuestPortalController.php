<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Support\InvoiceSettlement;
use App\Support\PdfRenderer;

class GuestPortalController extends Controller
{
    public function show(string $reference)
    {
        $booking = Booking::with(['property.building', 'agent', 'invoices.payments.bankAccount'])
            ->where('booking_reference', $reference)
            ->orWhere('invoice_number', $reference)
            ->firstOrFail();

        return view('guest.booking', compact('booking'));
    }

    public function invoice(string $reference)
    {
        $booking = $this->findBooking($reference);

        return PdfRenderer::downloadView('admin.bookings.pdf.invoice', compact('booking'), $booking->invoice_number.'.pdf');
    }

    public function confirmation(string $reference)
    {
        $booking = $this->findBooking($reference);
        \App\Support\InvoiceSettlement::assertBookingPaid($booking);

        return PdfRenderer::downloadView('admin.bookings.pdf.confirmation', compact('booking'), $booking->booking_reference.'-confirmation.pdf');
    }

    public function completePack(string $reference)
    {
        $booking = $this->findBooking($reference);
        InvoiceSettlement::assertBookingPaid($booking);

        return PdfRenderer::downloadView('admin.bookings.pdf.complete-pack', compact('booking'), $booking->booking_reference.'-complete-booking-pack.pdf');
    }

    public function invoiceDocument(string $reference, BookingInvoice $invoice)
    {
        $booking = $this->findBooking($reference);
        $this->assertInvoiceBelongsToBooking($invoice, $booking);
        $invoice->load('booking.property.building');

        return PdfRenderer::downloadView('admin.accounting.pdf.booking-invoice', compact('invoice'), $invoice->invoice_number.'.pdf');
    }

    public function invoiceReceipt(string $reference, BookingInvoice $invoice)
    {
        $booking = $this->findBooking($reference);
        $this->assertInvoiceBelongsToBooking($invoice, $booking);
        $invoice->load(['booking.property.building', 'payments.bankAccount']);
        abort_if($invoice->payments->isEmpty(), 422, 'No payment has been recorded for this invoice.');

        return PdfRenderer::downloadView('admin.bookings.pdf.payment-receipt', compact('invoice'), $invoice->invoice_number.'-payments.pdf');
    }

    public function invoiceConfirmation(string $reference, BookingInvoice $invoice)
    {
        $booking = $this->findBooking($reference);
        $this->assertInvoiceBelongsToBooking($invoice, $booking);
        InvoiceSettlement::assertPaid($invoice);
        $invoice->load('booking.property.building');

        return PdfRenderer::downloadView('admin.bookings.pdf.confirmation', ['booking' => $booking, 'invoice' => $invoice], $invoice->invoice_number.'-confirmation.pdf');
    }

    private function assertInvoiceBelongsToBooking(BookingInvoice $invoice, Booking $booking): void
    {
        abort_unless((string) $invoice->booking_id === (string) $booking->id, 404);
    }

    private function findBooking(string $reference): Booking
    {
        return Booking::with(['property.building', 'agent', 'invoices.payments.bankAccount'])
            ->where('booking_reference', $reference)
            ->orWhere('invoice_number', $reference)
            ->firstOrFail();
    }
}

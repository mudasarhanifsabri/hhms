<?php

namespace App\Mail;

use App\Models\BookingInvoice;
use App\Support\MediaStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class BookingPaidManagementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BookingInvoice $invoice) {}

    public function envelope(): Envelope
    {
        $booking = $this->invoice->booking;
        $unit = $this->invoice->booking?->property?->name ?? 'Unit';
        $guest = $booking?->guest_name ?: $booking?->tenant?->name ?: 'Guest';
        $checkIn = $this->invoice->period_from?->format('d M Y') ?? 'Date pending';

        return new Envelope(
            subject: 'Guest Check-In Notification - '.$guest.' - Unit '.$unit.' - '.$checkIn,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-paid-management');
    }

    public function attachments(): array
    {
        $booking = $this->invoice->booking;
        $documents = [
            'guest-document' => $booking?->guest_document,
            'tenant-id-front' => $booking?->tenant?->id_document,
            'tenant-id-back' => $booking?->tenant?->id_document_back,
        ];
        $seen = [];
        $attachments = [];

        foreach ($documents as $label => $storedPath) {
            if (blank($storedPath) || str_starts_with($storedPath, 'http://') || str_starts_with($storedPath, 'https://')) {
                continue;
            }

            $path = MediaStorage::path($storedPath);
            if (isset($seen[$path]) || ! Storage::disk(MediaStorage::disk())->exists($path)) {
                continue;
            }

            $seen[$path] = true;
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $filename = $label.($extension ? '.'.strtolower($extension) : '');
            $attachments[] = Attachment::fromStorageDisk(MediaStorage::disk(), $path)->as($filename);
        }

        return $attachments;
    }

    public function documentLabels(): array
    {
        $booking = $this->invoice->booking;

        return array_values(array_filter([
            filled($booking?->guest_document) ? 'Guest document' : null,
            filled($booking?->tenant?->id_document) ? 'Tenant ID - front' : null,
            filled($booking?->tenant?->id_document_back) ? 'Tenant ID - back' : null,
        ]));
    }
}

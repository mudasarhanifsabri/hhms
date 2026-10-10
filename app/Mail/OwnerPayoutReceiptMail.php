<?php

namespace App\Mail;

use App\Models\LandlordAccountEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerPayoutReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LandlordAccountEntry $entry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Owner Payout Receipt - '.$this->entry->reference,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.owner-payout-receipt');
    }
}

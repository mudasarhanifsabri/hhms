<?php
namespace App\Mail;
use App\Models\OwnerChargeInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
class OwnerChargeInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public OwnerChargeInvoice $invoice, public string $mailSubject, public ?string $customMessage, public string $pdfContent, public string $pdfFilename) {}
    public function envelope(): Envelope { return new Envelope(subject:$this->mailSubject); }
    public function content(): Content { return new Content(view:'emails.owner-charge-invoice'); }
    public function attachments(): array { return [Attachment::fromData(fn()=>$this->pdfContent,$this->pdfFilename)->withMime('application/pdf')]; }
}

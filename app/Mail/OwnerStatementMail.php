<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $owner,
        public array $statementData,
        public string $purpose,
        public ?string $customMessage,
        public string $pdfContent,
        public string $pdfFilename,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Owner Statement - '.$this->purpose.' - '.$this->statementData['period']['from']->format('d M Y').' to '.$this->statementData['period']['to']->format('d M Y'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.owner-statement');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}

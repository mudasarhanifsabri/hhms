<?php

namespace App\Mail;

use App\Models\Expense;
use App\Support\MediaStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerExpenseApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Expense $expense) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Approval Required - '.$this->expense->expense_no.' - AED '.number_format((float) $this->expense->gross_amount, 2));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.owner-expense-approval');
    }

    public function attachments(): array
    {
        return collect([
            'Quotation' => $this->expense->quotation_path,
            'Supplier-Invoice' => $this->expense->invoice_path,
        ])->filter()->map(fn ($path, $label) => Attachment::fromStorageDisk(MediaStorage::disk(), MediaStorage::path($path))
            ->as($label.'-'.basename($path)))->values()->all();
    }
}

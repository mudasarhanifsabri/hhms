<?php

namespace App\Jobs;

use App\Mail\OwnerStatementMail;
use App\Models\User;
use App\Support\OwnerStatementPdf;
use App\Support\PdfRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendOwnerStatementEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(
        public string $landlordId,
        public string $recipient,
        public ?string $cc,
        public string $purpose,
        public ?string $customMessage,
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?string $propertyId,
    ) {}

    public function handle(): void
    {
        $landlord = User::where('role', 'landlord')->findOrFail($this->landlordId);
        $statementData = OwnerStatementPdf::data($landlord, $this->dateFrom, $this->dateTo, $this->propertyId);
        $filename = 'owner-statement-'.Str::slug($landlord->name).'-'.$statementData['period']['to']->format('Y-m-d').'.pdf';
        $pdf = PdfRenderer::output(view('admin.landlords.pdf.account-statement', $statementData)->render(), ['format' => 'A4']);
        $pendingMail = Mail::to($this->recipient);

        if ($this->cc) {
            $pendingMail->cc($this->cc);
        }

        $pendingMail->send(new OwnerStatementMail(
            $landlord,
            $statementData,
            $this->purpose,
            $this->customMessage,
            $pdf,
            $filename,
        ));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Queued owner statement email failed.', [
            'landlord_id' => $this->landlordId,
            'recipient' => $this->recipient,
            'message' => $exception?->getMessage(),
        ]);
    }
}

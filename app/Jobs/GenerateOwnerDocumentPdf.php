<?php

namespace App\Jobs;

use App\Models\PropertyOwnerDocument;
use App\Support\MediaStorage;
use App\Support\OwnerDocumentPdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateOwnerDocumentPdf implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;
    public int $tries = 2;

    public function __construct(public string $documentId) {}

    public function handle(): void
    {
        if ((int) ini_get('memory_limit') > 0 && (int) ini_get('memory_limit') < 256) {
            ini_set('memory_limit', '256M');
        }
        $document = PropertyOwnerDocument::with(['property.building','property.ownerShares.owner','landlord'])->findOrFail($this->documentId);
        $html = view('owner-documents.document', [
            'document' => $document, 'property' => $document->property, 'landlord' => $document->landlord,
            'building' => $document->property->building, 'signatureData' => $document->signature_data,
            'signedByName' => $document->owner_signed_name ?: $document->landlord?->name,
            'companySignatureData' => $document->company_signature_data,
            'companySignedByName' => $document->company_signed_name,
        ])->render();
        $path = 'owner-documents/'.$document->reference_no.'.pdf';
        MediaStorage::put($path, OwnerDocumentPdf::output($html));
        $document->forceFill(['signed_html' => $html, 'signed_document_path' => $path])->save();
    }
}

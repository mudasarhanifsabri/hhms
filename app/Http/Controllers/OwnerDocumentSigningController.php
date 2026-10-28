<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateOwnerDocumentPdf;
use App\Models\PropertyOwnerDocument;
use App\Support\OwnerDocumentPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OwnerDocumentSigningController extends Controller
{
    public function show(string $token)
    {
        [$document, $party] = $this->documentAndParty($token);
        $documents = $this->packageDocuments($document);

        if (! $document->completed_at && $document->expires_at->isPast()) {
            $documents->each->update(['status' => 'expired']);
        } elseif ($party === 'owner' && ! $document->viewed_at) {
            $documents->each->update(['status' => 'viewed', 'viewed_at' => now()]);
        } elseif ($party === 'company' && ! $document->company_viewed_at) {
            $documents->each->update(['company_viewed_at' => now()]);
        }

        foreach ($documents as $item) {
            $item->unsigned_html = $this->renderDocumentHtml($item);
        }

        return view('owner-documents.sign', compact('document', 'documents', 'party', 'token'));
    }

    public function pdf(Request $request, string $token)
    {
        [$packageDocument] = $this->documentAndParty($token);
        $documents = $this->packageDocuments($packageDocument);
        $document = $documents->firstWhere('id', $request->query('document')) ?? $documents->first();

        return OwnerDocumentPdf::stream($this->renderDocumentHtml($document), $document->reference_no.'.pdf');
    }

    public function sign(Request $request, string $token)
    {
        [$document, $party] = $this->documentAndParty($token);
        $documents = $this->packageDocuments($document);
        if ($document->expires_at->isPast()) return back()->withErrors(['signature_data' => 'This agreement package has expired.']);
        if ($party === 'company' && ! $document->signed_at) return back()->withErrors(['signature_data' => 'The owner must sign before the company can countersign.']);
        if (($party === 'owner' && $document->signed_at) || ($party === 'company' && $document->company_signed_at)) return back()->with('success', ucfirst($party).' signature is already complete.');

        $data = $request->validate([
            'signature_data' => ['required','string','max:3000000','starts_with:data:image/png;base64,'],
            'signed_by_name' => ['required','string','max:255'],
            'accepted' => ['accepted'],
        ]);

        foreach ($documents as $item) {
            if ($party === 'owner') {
                $item->forceFill([
                    'signature_data' => $data['signature_data'], 'owner_signed_name' => $data['signed_by_name'],
                    'signed_at' => now(), 'status' => 'awaiting_company_signature', 'company_sent_at' => now(),
                ])->save();
            } else {
                $item->forceFill([
                    'company_signature_data' => $data['signature_data'], 'company_signed_name' => $data['signed_by_name'],
                    'company_signed_at' => now(), 'completed_at' => now(), 'status' => 'fully_signed',
                ])->save();
            }

            $html = $this->renderDocumentHtml($item->fresh(['property.building','property.ownerShares.owner','landlord']));
            $item->forceFill(['signed_html' => $html])->save();
            if ($party === 'company') GenerateOwnerDocumentPdf::dispatch($item->id);
        }

        if ($party === 'owner') $this->sendCompanyLink($documents->first()->fresh(['property','landlord']));

        return redirect()->route('owner-documents.show', $token)->with('success', $party === 'owner'
            ? 'Owner signature saved for all three documents. The package is now awaiting company countersignature.'
            : 'Agreement package fully signed. The final documents are ready.');
    }

    private function documentAndParty(string $token): array
    {
        $document = PropertyOwnerDocument::with(['property.building','property.ownerShares.owner','landlord'])->where('signing_token', $token)->first();
        if ($document) return [$document, 'owner'];
        $document = PropertyOwnerDocument::with(['property.building','property.ownerShares.owner','landlord'])->where('company_token', $token)->firstOrFail();

        return [$document, 'company'];
    }

    private function packageDocuments(PropertyOwnerDocument $document)
    {
        return PropertyOwnerDocument::with(['property.building','property.ownerShares.owner','landlord'])
            ->where('package_id', $document->package_id ?: $document->id)
            ->orderByRaw("CASE type WHEN 'noc' THEN 1 WHEN 'management_letter' THEN 2 ELSE 3 END")
            ->get()
            ->whenEmpty(fn ($items) => $items->push($document));
    }

    private function renderDocumentHtml(PropertyOwnerDocument $document): string
    {
        return view('owner-documents.document', [
            'document' => $document, 'property' => $document->property, 'landlord' => $document->landlord,
            'building' => $document->property->building, 'signatureData' => $document->signature_data,
            'signedByName' => $document->owner_signed_name ?: $document->landlord?->name,
            'companySignatureData' => $document->company_signature_data,
            'companySignedByName' => $document->company_signed_name,
        ])->render();
    }

    private function sendCompanyLink(PropertyOwnerDocument $document): void
    {
        if (! $document->company_signer_email) return;
        try {
            Mail::html('<p>The owner has signed the '.$document->agreement_type_label.' package for unit <strong>'.e($document->property?->name).'</strong>.</p><p><a href="'.e(route('owner-documents.show', $document->company_token)).'">Review and countersign all documents</a></p>',
                fn ($message) => $message->to($document->company_signer_email)->subject('Company Countersignature Required - '.$document->property?->name));
        } catch (Throwable $exception) {
            Log::warning('Company agreement signing email failed', ['document_id' => $document->id, 'error' => $exception->getMessage()]);
        }
    }
}

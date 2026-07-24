<?php

namespace App\Http\Controllers;

use App\Models\PropertyOwnerDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OwnerDocumentSigningController extends Controller
{
    public function show(string $token)
    {
        $document = PropertyOwnerDocument::with(['property.building', 'landlord'])
            ->where('signing_token', $token)
            ->firstOrFail();

        if (! $document->signed_at && $document->expires_at->isPast() && $document->status !== 'expired') {
            $document->update(['status' => 'expired']);
        }

        if ($document->status === 'sent') {
            $document->update(['status' => 'viewed', 'viewed_at' => now()]);
        }

        $currentHtml = $this->renderDocumentHtml(
            $document,
            $document->signed_at ? $document->signature_data : null,
            $document->signed_at ? $document->landlord?->name : null
        );

        if ($document->signed_at) {
            $document->signed_html = $currentHtml;
        } else {
            $document->unsigned_html = $currentHtml;
        }

        return view('owner-documents.sign', compact('document'));
    }

    public function pdf(string $token)
    {
        $document = PropertyOwnerDocument::with(['property.building', 'landlord'])
            ->where('signing_token', $token)
            ->firstOrFail();

        $html = $this->renderDocumentHtml(
            $document,
            $document->signed_at ? $document->signature_data : null,
            $document->signed_at ? $document->landlord?->name : null
        );

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->stream($document->reference_no . '.pdf');
    }

    public function sign(Request $request, string $token)
    {
        $document = PropertyOwnerDocument::with(['property.building', 'landlord'])
            ->where('signing_token', $token)
            ->firstOrFail();

        if ($document->signed_at) {
            return redirect()->route('owner-documents.show', $document->signing_token)
                ->with('success', 'Document already signed.');
        }

        if ($document->expires_at->isPast()) {
            $document->update(['status' => 'expired']);

            return redirect()->route('owner-documents.show', $document->signing_token)
                ->withErrors(['signature_data' => 'This document has expired. Please request a new signing link.']);
        }

        $validated = $request->validate([
            'signature_data' => 'required|string',
            'signed_by_name' => 'required|string|max:255',
        ]);

        $document->forceFill([
            'signature_data' => $validated['signature_data'],
            'status' => 'signed',
            'signed_at' => now(),
        ]);

        $signedHtml = $this->renderDocumentHtml($document, $validated['signature_data'], $validated['signed_by_name']);

        $path = 'owner-documents/' . $document->reference_no . '.pdf';
        Storage::disk('public')->put($path, Pdf::loadHTML($signedHtml)->setPaper('a4')->output());

        $document->forceFill([
            'signed_html' => $signedHtml,
            'signed_document_path' => $path,
        ])->save();

        return redirect()->route('owner-documents.show', $document->signing_token)
            ->with('success', 'Document signed successfully.');
    }

    private function renderDocumentHtml(PropertyOwnerDocument $document, ?string $signatureData, ?string $signedByName): string
    {
        $document->loadMissing(['property.building', 'property.ownerShares.owner', 'landlord']);

        return view('owner-documents.document', [
            'document' => $document,
            'property' => $document->property,
            'landlord' => $document->landlord,
            'building' => $document->property->building,
            'signatureData' => $signatureData,
            'signedByName' => $signedByName,
        ])->render();
    }
}

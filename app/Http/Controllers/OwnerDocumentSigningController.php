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

        return view('owner-documents.sign', compact('document'));
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

        $signedHtml = view('owner-documents.document', [
            'document' => $document,
            'property' => $document->property,
            'landlord' => $document->landlord,
            'building' => $document->property->building,
            'signatureData' => $validated['signature_data'],
            'signedByName' => $validated['signed_by_name'],
        ])->render();

        $path = 'owner-documents/' . $document->reference_no . '.pdf';
        Storage::disk('public')->put($path, Pdf::loadHTML($signedHtml)->setPaper('a4')->output());

        $document->forceFill([
            'signed_html' => $signedHtml,
            'signed_document_path' => $path,
        ])->save();

        return redirect()->route('owner-documents.show', $document->signing_token)
            ->with('success', 'Document signed successfully.');
    }
}

<?php

namespace App\Http\Controllers\admin\properties;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyOwnerDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyOwnerDocumentController extends Controller
{
    public function index(Property $property)
    {
        $property->load(['landlord', 'building', 'ownerDocuments']);
        $documents = $property->ownerDocuments()->latest()->get();

        return view('admin.properties.owner-documents.index', compact('property', 'documents'));
    }

    public function store(Request $request, Property $property)
    {
        $validated = $request->validate([
            'furniture_amount' => 'nullable|numeric|min:0',
            'startup_dtcm_fee' => 'nullable|numeric|min:0',
        ]);

        $property->load(['landlord', 'building']);
        $furnitureAmount = (float) ($validated['furniture_amount'] ?? 0);
        $startupDtcmFee = (float) ($validated['startup_dtcm_fee'] ?? 0);
        $vatAmount = round($furnitureAmount * 0.05, 2);
        $totalAmount = $furnitureAmount + $startupDtcmFee + $vatAmount;

        foreach (PropertyOwnerDocument::TYPES as $type => $title) {
            $referenceNo = $this->referenceNo($type);
            $document = new PropertyOwnerDocument([
                'property_id' => $property->id,
                'landlord_id' => $property->landlord_id,
                'type' => $type,
                'title' => $title,
                'reference_no' => $referenceNo,
                'status' => 'sent',
                'signing_token' => Str::random(48),
                'furniture_amount' => $furnitureAmount,
                'startup_dtcm_fee' => $startupDtcmFee,
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'sent_at' => now(),
                'expires_at' => now()->addYear()->toDateString(),
            ]);

            $document->unsigned_html = view('owner-documents.document', [
                'document' => $document,
                'property' => $property,
                'landlord' => $property->landlord,
                'building' => $property->building,
                'signatureData' => null,
                'signedByName' => null,
            ])->render();
            $document->save();
        }

        return redirect()->route('admin.property.owner-documents.index', $property->id)
            ->with('success', 'Owner documents generated and marked as sent. Share the signing links with the owner.');
    }

    private function referenceNo(string $type): string
    {
        $prefix = match ($type) {
            'noc' => 'NOC',
            'management_letter' => 'PML',
            default => 'PMC',
        };

        do {
            $referenceNo = $prefix . '-' . now()->format('ymd') . '-' . random_int(1000, 9999);
        } while (PropertyOwnerDocument::where('reference_no', $referenceNo)->exists());

        return $referenceNo;
    }
}

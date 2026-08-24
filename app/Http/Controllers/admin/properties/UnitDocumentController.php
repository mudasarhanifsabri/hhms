<?php

namespace App\Http\Controllers\admin\properties;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\UnitDocument;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class UnitDocumentController extends Controller
{
    public function index(Property $property)
    {
        $property->load(['building', 'landlord', 'ownerShares.owner']);

        return view('admin.properties.document-wallet.index', [
            'property' => $property,
            'documents' => $property->unitDocuments()->with('owner')->latest()->get(),
            'signingDocuments' => $property->ownerDocuments()->latest()->get(),
            'types' => UnitDocument::TYPES,
            'owners' => $property->ownerShares->pluck('owner')->filter()->prepend($property->landlord)->filter()->unique('id')->values(),
        ]);
    }

    public function store(Request $request, Property $property)
    {
        $data = $this->validated($request, true);
        $this->applyDefaultExpiry($data);
        $this->ensureOwnerBelongsToUnit($property, $data['owner_id'] ?? null);
        $data['file_path'] = MediaStorage::store($request->file('document'), 'unit-documents');
        $property->unitDocuments()->create($data);

        return back()->with('success', 'Document added to the Unit Document Wallet.');
    }

    public function update(Request $request, Property $property, UnitDocument $document)
    {
        abort_unless($document->property_id === $property->id, 404);
        $data = $this->validated($request, false);
        $this->applyDefaultExpiry($data);
        $this->ensureOwnerBelongsToUnit($property, $data['owner_id'] ?? null);
        if ($request->hasFile('document')) {
            if ($document->source !== 'legacy_property') {
                Storage::disk(MediaStorage::disk())->delete(MediaStorage::path($document->file_path));
            }
            $data['file_path'] = MediaStorage::store($request->file('document'), 'unit-documents');
            $data['source'] = 'uploaded';
        }
        $document->update($data);

        return back()->with('success', 'Document updated.');
    }

    public function destroy(Property $property, UnitDocument $document)
    {
        abort_unless($document->property_id === $property->id, 404);
        if ($document->source !== 'legacy_property') {
            Storage::disk(MediaStorage::disk())->delete(MediaStorage::path($document->file_path));
        }
        $document->delete();

        return back()->with('success', 'Document removed from the wallet.');
    }

    private function validated(Request $request, bool $fileRequired): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys(UnitDocument::TYPES))],
            'custom_title' => ['nullable', 'required_if:type,custom', 'string', 'max:255'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document' => [$fileRequired ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'],
        ]);
    }

    private function ensureOwnerBelongsToUnit(Property $property, ?string $ownerId): void
    {
        if (! $ownerId) return;
        abort_unless($property->landlord_id === $ownerId || $property->ownerShares()->where('owner_id', $ownerId)->exists(), 422);
    }

    private function applyDefaultExpiry(array &$data): void
    {
        if (! empty($data['issue_date']) && empty($data['expires_at'])) {
            $data['expires_at'] = Carbon::parse($data['issue_date'])->addYear()->toDateString();
        }
    }
}

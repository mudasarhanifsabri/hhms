<?php

namespace App\Http\Controllers\admin\maintainers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Property;
use App\Support\PdfRenderer;
use Illuminate\Http\Response;

class MaintainerController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 10);
        $maintainers = User::where('role', 'maintainer')->paginate($perPage);
        $totalMaintainers = User::where('role', 'maintainer')->count();

        return response()->view('admin.maintainers.index', compact('maintainers', 'totalMaintainers', 'perPage'));
    }

    public function showGrid(Request $request): Response
    {
        $perPage = $request->input('per_page', 10);
        $maintainers = User::where('role', 'maintainer')->paginate($perPage);
        $totalMaintainers = User::where('role', 'maintainer')->count();

        return response()->view('admin.maintainers.showgrid', compact('maintainers', 'totalMaintainers', 'perPage'));
    }

    public function show($id)
    {
        $maintainer = User::where('role', 'maintainer')->findOrFail($id);
        $relatedProperties = Property::where('status', 'vacant')->latest()->limit(4)->get();
        $profileUser = $maintainer;
        $roleLabel = 'Maintainer';
        $editRoute = route('admin.maintainer.edit', $maintainer->id);
        $backRoute = route('admin.maintainer.index');
        $bankRoute = route('admin.maintainer.updateBank', $maintainer->id);
        $propertiesTitle = 'Units Needing Attention';
        $summaryCards = [
            ['label' => 'Profile Status', 'value' => $maintainer->is_active ? 'Active' : 'Inactive'],
            ['label' => 'Emergency Contact', 'value' => $maintainer->emergency_contact_name ? 'Provided' : 'Missing'],
            ['label' => 'Bank Details', 'value' => $maintainer->bank_account_number ? 'Provided' : 'Missing'],
        ];

        return view('admin.maintainers.show', compact(
            'maintainer',
            'profileUser',
            'roleLabel',
            'editRoute',
            'backRoute',
            'bankRoute',
            'propertiesTitle',
            'summaryCards',
            'relatedProperties'
        ));
    }

    public function create()
    {
        return view('admin.maintainers.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'id_document' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required',
            'dob' => 'required|date',
            'eid_passport_no' => 'required|string|max:50',
            'address' => 'required|string|max:255',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required',
            'emergency_contact_email' => 'required|email|max:255',
            'emergency_contact_relationship' => 'required|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_holder' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
        ]);

        try {
            $profilePhotoPath = $this->uploadFile($request, 'profile_photo', 'profile_photos');
            $idDocumentPath = $this->uploadFile($request, 'id_document', 'id_documents');

            $maintainer = User::create(array_merge($validatedData, [
                'password' => Hash::make(Str::random(8)),
                'role' => 'maintainer',
                'profile_photo' => $profilePhotoPath,
                'id_document' => $idDocumentPath,
            ]));

            return redirect()->route('admin.maintainer.index')
                ->with('success', 'Maintainer created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating maintainer: ' . $e->getMessage());

            return redirect()->route('admin.maintainer.create')
                ->withErrors(['error' => 'An error occurred while creating the maintainer.'])
                ->withInput();
        }
    }

    private function uploadFile(Request $request, $field, $folder)
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);
            if (is_array($file)) $file = $file[0];

            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path($folder);

            if (!file_exists($destination)) mkdir($destination, 0755, true);

            $file->move($destination, $filename);

            return $folder . '/' . $filename;
        }

        return null;
    }

    public function edit($id)
    {
        $maintainer = User::findOrFail($id);
        return view('admin.maintainers.edit', compact('maintainer'));
    }

    public function update(Request $request, $id)
    {
        $maintainer = User::findOrFail($id);

        $validatedData = $request->validate([
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'id_document' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $maintainer->id,
            'phone' => 'required',
            'dob' => 'required|date',
            'eid_passport_no' => 'required|string|max:50',
            'address' => 'required|string|max:255',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required',
            'emergency_contact_email' => 'required|email|max:255',
            'emergency_contact_relationship' => 'required|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_holder' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
        ]);

        try {
            $profilePhotoPath = $this->uploadFile($request, 'profile_photo', 'profile_photos');
            $idDocumentPath = $this->uploadFile($request, 'id_document', 'id_documents');

            $maintainer->update(array_merge($validatedData, [
                'profile_photo' => $profilePhotoPath ?? $maintainer->profile_photo,
                'id_document' => $idDocumentPath ?? $maintainer->id_document,
            ]));

            return redirect()->route('admin.maintainer.index')
                ->with('success', 'Maintainer updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating maintainer: ' . $e->getMessage());

            return redirect()->route('admin.maintainer.edit', $maintainer->id)
                ->withErrors(['error' => 'An error occurred while updating the maintainer.'])
                ->withInput();
        }
    }

    public function genratePdf()
    {
        $maintainers = User::where('role', 'maintainer')->get();
        $totalMaintainers = $maintainers->count();

        return PdfRenderer::downloadView('admin.pdf.maintainers.list', compact('maintainers', 'totalMaintainers'), 'maintainers_list.pdf');
    }

    public function destroy($id)
    {
        try {
            $maintainer = User::where('role', 'maintainer')->findOrFail($id);
            $maintainer->delete();

            return redirect()->route('admin.maintainer.index')->with('success', 'Maintainer deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting maintainer: ' . $e->getMessage());

            return redirect()->route('admin.maintainer.index')->withErrors(['error' => 'Failed to delete maintainer.']);
        }
    }

    public function updateBankDetails(Request $request, $id)
    {
        $maintainer = User::findOrFail($id);

        $validatedData = $request->validate([
            'bank_name' => 'nullable|string|max:255',
            'bank_account_holder' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
        ]);

        try {
            $maintainer->update($validatedData);

            return redirect()->route('admin.maintainer.show', $maintainer->id)
                ->with('success', 'Bank details updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating bank details: ' . $e->getMessage());

            return redirect()->route('admin.maintainer.show', $maintainer->id)
                ->withErrors(['error' => 'Failed to update bank details. Please try again.']);
        }
    }
}

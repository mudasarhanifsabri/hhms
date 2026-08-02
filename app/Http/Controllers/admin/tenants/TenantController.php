<?php

namespace App\Http\Controllers\admin\tenants;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Property;
use App\Support\MediaStorage;
use App\Support\PdfRenderer;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\Notification;
// use App\Notifications\TenantCreated;
// use App\Notifications\TenantUpdated;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 10);
        $tenants = User::where('role', 'tenant')->paginate($perPage);
        $totalTenants = User::where('role', 'tenant')->count();

        return response()->view('admin.tenants.index', compact('tenants', 'totalTenants', 'perPage'));
    }

    public function showGrid(Request $request): Response
    {
        $perPage = $request->input('per_page', 10);
        $tenants = User::where('role', 'tenant')->paginate($perPage);
        $totalTenants = User::where('role', 'tenant')->count();

        return response()->view('admin.tenants.showgrid', compact('tenants', 'totalTenants', 'perPage'));
    }

    public function show($id)
    {
        $tenant = User::where('role', 'tenant')->findOrFail($id);
        $relatedProperties = Property::latest()->limit(4)->get();
        $profileUser = $tenant;
        $roleLabel = 'Tenant';
        $editRoute = route('admin.tenant.edit', $tenant->id);
        $backRoute = route('admin.tenant.index');
        $bankRoute = route('admin.tenant.updateBank', $tenant->id);
        $propertiesTitle = 'Recent Units';
        $summaryCards = [
            ['label' => 'Profile Status', 'value' => $tenant->is_active ? 'Active' : 'Inactive'],
            ['label' => 'Emergency Contact', 'value' => $tenant->emergency_contact_name ? 'Provided' : 'Missing'],
            ['label' => 'Bank Details', 'value' => $tenant->bank_account_number ? 'Provided' : 'Missing'],
        ];

        return view('admin.tenants.show', compact(
            'tenant',
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
        return view('admin.tenants.create');
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

            $tenant = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'dob' => $validatedData['dob'],
                'eid_passport_no' => $validatedData['eid_passport_no'],
                'address' => $validatedData['address'],
                'emergency_contact_name' => $validatedData['emergency_contact_name'],
                'emergency_contact_phone' => $validatedData['emergency_contact_phone'],
                'emergency_contact_email' => $validatedData['emergency_contact_email'],
                'emergency_contact_relationship' => $validatedData['emergency_contact_relationship'],
                'bank_name' => $validatedData['bank_name'] ?? null,
                'bank_account_holder' => $validatedData['bank_account_holder'] ?? null,
                'bank_account_number' => $validatedData['bank_account_number'] ?? null,
                'swift_code' => $validatedData['swift_code'] ?? null,
                'iban' => $validatedData['iban'] ?? null,
                'bank_branch' => $validatedData['bank_branch'] ?? null,
                'password' => Hash::make(Str::random(8)),
                'role' => 'tenant',
                'profile_photo' => $profilePhotoPath,
                'id_document' => $idDocumentPath,
            ]);

            return redirect()->route('admin.tenant.index')
                ->with('success', 'Tenant created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating tenant: ' . $e->getMessage());

            return redirect()->route('admin.tenant.create')
                ->withErrors(['error' => 'An error occurred while creating the tenant. Please try again.'])
                ->withInput();
        }
    }

    private function uploadFile(Request $request, $field, $folder)
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);
            if (is_array($file)) {
                $file = $file[0];
            }

            return MediaStorage::store($file, $folder);
        }

        return null;
    }

    public function edit($id)
    {
        $tenant = User::findOrFail($id);
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, $id)
    {
        $tenant = User::findOrFail($id);

        $validatedData = $request->validate([
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'id_document' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $tenant->id,
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

            $tenant->update([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'dob' => $validatedData['dob'],
                'eid_passport_no' => $validatedData['eid_passport_no'],
                'address' => $validatedData['address'],
                'emergency_contact_name' => $validatedData['emergency_contact_name'],
                'emergency_contact_phone' => $validatedData['emergency_contact_phone'],
                'emergency_contact_email' => $validatedData['emergency_contact_email'],
                'emergency_contact_relationship' => $validatedData['emergency_contact_relationship'],
                'bank_name' => $validatedData['bank_name'] ?? null,
                'bank_account_holder' => $validatedData['bank_account_holder'] ?? null,
                'bank_account_number' => $validatedData['bank_account_number'] ?? null,
                'swift_code' => $validatedData['swift_code'] ?? null,
                'iban' => $validatedData['iban'] ?? null,
                'bank_branch' => $validatedData['bank_branch'] ?? null,
                'profile_photo' => $profilePhotoPath ?? $tenant->profile_photo,
                'id_document' => $idDocumentPath ?? $tenant->id_document,
            ]);

            return redirect()->route('admin.tenant.index')
                ->with('success', 'Tenant updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating tenant: ' . $e->getMessage());

            return redirect()->route('admin.tenant.edit', $tenant->id)
                ->withErrors(['error' => 'An error occurred while updating the tenant. Please try again.'])
                ->withInput();
        }
    }

    public function genratePdf()
    {
        $tenants = User::where('role', 'tenant')->get();
        $totalTenants = $tenants->count();

        return PdfRenderer::downloadView('admin.pdf.tenants.list', compact('tenants', 'totalTenants'), 'tenants_list.pdf');
    }



    public function destroy($id)
    {
        try {
            $tenant = User::where('role', 'tenant')->findOrFail($id);

            $tenant->delete();

            return redirect()->route('admin.tenant.index')->with('success', 'Tenant deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting tenant: ' . $e->getMessage());

            return redirect()->route('admin.tenant.index')->withErrors(['error' => 'Failed to delete tenant.']);
        }
    }

    public function updateBankDetails(Request $request, $id)
    {
        $tenant = User::findOrFail($id);

        $validatedData = $request->validate([
            'bank_name' => 'nullable|string|max:255',
            'bank_account_holder' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
        ]);

        try {
            $tenant->update([
                'bank_name' => $request->bank_name,
                'bank_account_holder' => $request->bank_account_holder,
                'bank_account_number' => $request->bank_account_number,
                'swift_code' => $request->swift_code,
                'iban' => $request->iban,
                'bank_branch' => $request->bank_branch,
            ]);

            return redirect()->route('admin.tenant.show', $tenant->id)
                ->with('success', 'Bank details updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating bank details: ' . $e->getMessage());

            return redirect()->route('admin.tenant.show', $tenant->id)
                ->withErrors(['error' => 'Failed to update bank details. Please try again.']);
        }
    }
}

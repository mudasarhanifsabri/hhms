<?php

namespace App\Http\Controllers\admin\agents;

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

class AgentController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 10);
        $agents = User::where('role', 'agent')->paginate($perPage);
        $totalAgents = User::where('role', 'agent')->count();

        return response()->view('admin.agents.index', compact('agents', 'totalAgents', 'perPage'));
    }

    public function showGrid(Request $request): Response
    {
        $perPage = $request->input('per_page', 10);
        $agents = User::where('role', 'agent')->paginate($perPage);
        $totalAgents = User::where('role', 'agent')->count();

        return response()->view('admin.agents.showgrid', compact('agents', 'totalAgents', 'perPage'));
    }

    public function show($id)
    {
        $agent = User::where('role', 'agent')->findOrFail($id);
        $relatedProperties = Property::latest()->limit(4)->get();
        $profileUser = $agent;
        $roleLabel = 'Agent';
        $editRoute = route('admin.agent.edit', $agent->id);
        $backRoute = route('admin.agent.index');
        $bankRoute = route('admin.agent.updateBank', $agent->id);
        $propertiesTitle = 'Recent Units';
        $summaryCards = [
            ['label' => 'Commission', 'value' => number_format((float) $agent->agent_commission, 2) . '%'],
            ['label' => 'Profile Status', 'value' => $agent->is_active ? 'Active' : 'Inactive'],
            ['label' => 'Bank Details', 'value' => $agent->bank_account_number ? 'Provided' : 'Missing'],
        ];

        return view('admin.agents.show', compact(
            'agent',
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
        return view('admin.agents.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'id_document' => 'nullable|mimes:pdf,jpg,jpeg,png|max:10240',
            'id_document_back' => 'nullable|mimes:pdf,jpg,jpeg,png|max:10240',
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required',
            'dob' => 'nullable|date',
            'eid_passport_no' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'gender' => 'nullable|string|max:30',
            'id_issue_date' => 'nullable|date',
            'id_expiry_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable',
            'emergency_contact_email' => 'nullable|email|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:50',
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
            $idDocumentBackPath = $this->uploadFile($request, 'id_document_back', 'id_documents');

            $agent = User::create([
                'name' => $validatedData['name'],
                'name_ar' => $validatedData['name_ar'] ?? null,
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'dob' => $validatedData['dob'] ?? null,
                'eid_passport_no' => $validatedData['eid_passport_no'] ?? null,
                'nationality' => $validatedData['nationality'] ?? null,
                'gender' => $validatedData['gender'] ?? null,
                'id_issue_date' => $validatedData['id_issue_date'] ?? null,
                'id_expiry_date' => $validatedData['id_expiry_date'] ?? null,
                'address' => $validatedData['address'] ?? null,
                'emergency_contact_name' => $validatedData['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validatedData['emergency_contact_phone'] ?? null,
                'emergency_contact_email' => $validatedData['emergency_contact_email'] ?? null,
                'emergency_contact_relationship' => $validatedData['emergency_contact_relationship'] ?? null,
                'bank_name' => $validatedData['bank_name'] ?? null,
                'bank_account_holder' => $validatedData['bank_account_holder'] ?? null,
                'bank_account_number' => $validatedData['bank_account_number'] ?? null,
                'swift_code' => $validatedData['swift_code'] ?? null,
                'iban' => $validatedData['iban'] ?? null,
                'bank_branch' => $validatedData['bank_branch'] ?? null,
                'password' => Hash::make(Str::random(8)),
                'role' => 'agent',
                'profile_photo' => $profilePhotoPath,
                'id_document' => $idDocumentPath,
                'id_document_back' => $idDocumentBackPath,
            ]);

            return redirect()->route('admin.agent.index')
                ->with('success', 'Agent created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating agent: ' . $e->getMessage());

            return redirect()->route('admin.agent.create')
                ->withErrors(['error' => 'An error occurred while creating the agent. Please try again.'])
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
        $agent = User::findOrFail($id);
        return view('admin.agents.edit', compact('agent'));
    }

    public function update(Request $request, $id)
    {
        $agent = User::findOrFail($id);

        $validatedData = $request->validate([
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'id_document' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $agent->id,
            'phone' => 'required',
            'dob' => 'nullable|date',
            'eid_passport_no' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable',
            'emergency_contact_email' => 'nullable|email|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:50',
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

            $agent->update([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'dob' => $validatedData['dob'] ?? null,
                'eid_passport_no' => $validatedData['eid_passport_no'] ?? null,
                'address' => $validatedData['address'] ?? null,
                'emergency_contact_name' => $validatedData['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validatedData['emergency_contact_phone'] ?? null,
                'emergency_contact_email' => $validatedData['emergency_contact_email'] ?? null,
                'emergency_contact_relationship' => $validatedData['emergency_contact_relationship'] ?? null,
                'bank_name' => $validatedData['bank_name'] ?? null,
                'bank_account_holder' => $validatedData['bank_account_holder'] ?? null,
                'bank_account_number' => $validatedData['bank_account_number'] ?? null,
                'swift_code' => $validatedData['swift_code'] ?? null,
                'iban' => $validatedData['iban'] ?? null,
                'bank_branch' => $validatedData['bank_branch'] ?? null,
                'profile_photo' => $profilePhotoPath ?? $agent->profile_photo,
                'id_document' => $idDocumentPath ?? $agent->id_document,
            ]);

            return redirect()->route('admin.agent.index')
                ->with('success', 'Agent updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating agent: ' . $e->getMessage());

            return redirect()->route('admin.agent.edit', $agent->id)
                ->withErrors(['error' => 'An error occurred while updating the agent. Please try again.'])
                ->withInput();
        }
    }

    public function genratePdf()
    {
        $agents = User::where('role', 'agent')->get();
        $totalAgents = $agents->count();

        return PdfRenderer::downloadView('admin.pdf.agents.list', compact('agents', 'totalAgents'), 'agents_list.pdf');
    }

    public function destroy($id)
    {
        try {
            $agent = User::where('role', 'agent')->findOrFail($id);

            $agent->delete();

            return redirect()->route('admin.agent.index')->with('success', 'Agent deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting agent: ' . $e->getMessage());

            return redirect()->route('admin.agent.index')->withErrors(['error' => 'Failed to delete agent.']);
        }
    }

    public function updateBankDetails(Request $request, $id)
    {
        $agent = User::findOrFail($id);

        $validatedData = $request->validate([
            'bank_name' => 'nullable|string|max:255',
            'bank_account_holder' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
        ]);

        try {
            $agent->update($validatedData);

            return redirect()->route('admin.agent.show', $agent->id)
                ->with('success', 'Bank details updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating bank details: ' . $e->getMessage());

            return redirect()->route('admin.agent.show', $agent->id)
                ->withErrors(['error' => 'Failed to update bank details. Please try again.']);
        }
    }
}

<?php




namespace App\Http\Controllers\admin\landlords;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Property;
use App\Models\LandlordAccountEntry;
use App\Support\MediaStorage;
use App\Support\PdfRenderer;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use App\Notifications\LandlordCreated;
use Throwable;



class LandlordController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = in_array($request->integer('per_page', 10), [5, 10, 25, 50, 100], true)
            ? $request->integer('per_page', 10)
            : 10;
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $landlords = User::where('role', 'landlord')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('eid_passport_no', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true),
                fn ($query) => $query->where('is_active', $status === 'active'))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
        $this->attachOwnerUnitSummary($landlords->getCollection());
        $totalLandlords = User::where('role', 'landlord')->count();

        return response()->view('admin.landlords.index', compact('landlords', 'totalLandlords', 'perPage', 'search', 'status'));
    }

    public function showGrid(Request $request): Response
{
        $perPage = in_array($request->integer('per_page', 10), [5, 10, 25, 50, 100], true)
            ? $request->integer('per_page', 10)
            : 10;
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $landlords = User::where('role', 'landlord')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('eid_passport_no', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true),
                fn ($query) => $query->where('is_active', $status === 'active'))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
        $this->attachOwnerUnitSummary($landlords->getCollection());
        $totalLandlords = User::where('role', 'landlord')->count();

        return response()->view('admin.landlords.showgrid', compact('landlords', 'totalLandlords', 'perPage', 'search', 'status'));
}

    public function show($id)
    {
        $landlord = User::where('role', 'landlord')->findOrFail($id);
        $relatedProperties = $this->ownerUnitsQuery($landlord->id)->latest()->limit(8)->get();
        $ownedPropertiesCount = $this->ownerUnitsQuery($landlord->id)->count();
        $rentedPropertiesCount = $this->ownerUnitsQuery($landlord->id)->whereIn('status', ['booked', 'rented'])->count();
        $profileUser = $landlord;
        $roleLabel = 'Owner / Landlord';
        $editRoute = route('admin.landlord.edit', $landlord->id);
        $backRoute = route('admin.landlord.index');
        $bankRoute = route('admin.landlord.updateBank', $landlord->id);
        $welcomeEmailRoute = route('admin.landlord.sendWelcomeEmail', $landlord->id);
        $accountStatementRoute = route('admin.landlord.account-statement', $landlord->id);
        $ownedPropertiesRoute = route('admin.landlord.owned-properties', $landlord->id);
        $propertiesTitle = 'Owned Units';
        $summaryCards = [
            ['label' => 'Owned Units', 'value' => $ownedPropertiesCount],
            ['label' => 'Rented Units', 'value' => $rentedPropertiesCount],
            ['label' => 'Balance', 'value' => number_format($this->accountTotalsFor($landlord->id)['balance'], 2) . ' AED'],
        ];

        return view('admin.landlords.show', compact(
            'landlord',
            'profileUser',
            'roleLabel',
            'editRoute',
            'backRoute',
            'bankRoute',
            'welcomeEmailRoute',
            'accountStatementRoute',
            'ownedPropertiesRoute',
            'propertiesTitle',
            'summaryCards',
            'relatedProperties'
        ));
    }

    public function accountStatement(Request $request, $id)
    {
        $landlord = User::where('role', 'landlord')->findOrFail($id);
        $relatedProperties = $this->ownerUnitsQuery($landlord->id)->latest()->get();
        $filters = $this->accountStatementFilters($request);
        $perPage = $request->integer('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
        $accountEntries = $this->accountEntriesQuery($landlord->id, $filters)
            ->statementOrder()
            ->paginate($perPage)
            ->withQueryString();
        $statementBalances = LandlordAccountEntry::statementBalancesFor($landlord->id);
        $accountEntries->getCollection()->each(fn ($entry) => $entry->setAttribute('balance_after', $statementBalances[$entry->id]));
        $accountTotals = $this->accountTotalsFor($landlord->id, $filters);
        $unitTotals = $this->accountEntriesQuery($landlord->id, $filters)->get()
            ->groupBy(fn (LandlordAccountEntry $entry) => $entry->property_id ?: 'general')
            ->map(function ($entries) {
                $credit = (float) $entries->where('direction', 'credit')->sum('amount');
                $debit = (float) $entries->where('direction', 'debit')->sum('amount');
                return ['property' => $entries->first()?->property, 'credit' => $credit, 'debit' => $debit, 'balance' => $credit - $debit];
            });
        $ownerLoanSummary = [
            'advanced' => (float) LandlordAccountEntry::where('landlord_id', $landlord->id)->whereIn('type', ['owner_loan', 'furnishing'])->where('direction', 'debit')->sum('amount'),
            'repaid' => (float) LandlordAccountEntry::where('landlord_id', $landlord->id)->where('type', 'loan_repayment')->where('direction', 'credit')->sum('amount'),
            'receivable' => max(0, -(float) $this->accountTotalsFor($landlord->id)['balance']),
        ];
        $accountEntryTypes = LandlordAccountEntry::allTypes() + LandlordAccountEntry::query()
            ->select('type')->distinct()->pluck('type')
            ->mapWithKeys(fn (string $type) => [$type => str($type)->replace('_', ' ')->headline()->toString()])
            ->all();
        $accountEntryRoute = route('admin.landlord.account-entry.store', $landlord->id);
        $detailsRoute = route('admin.landlord.show', $landlord->id);
        $ownedPropertiesRoute = route('admin.landlord.owned-properties', $landlord->id);
        $backRoute = route('admin.landlord.index');
        $statementPdfRoute = route('admin.landlord.account-statement.pdf', array_filter([
            'id' => $landlord->id,
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'property_id' => $filters['property_id'],
        ]));

        return view('admin.landlords.account-statement', compact(
            'landlord',
            'relatedProperties',
            'accountEntries',
            'accountTotals',
            'unitTotals',
            'ownerLoanSummary',
            'accountEntryTypes',
            'accountEntryRoute',
            'detailsRoute',
            'ownedPropertiesRoute',
            'backRoute',
            'statementPdfRoute',
            'filters',
            'perPage'
        ));
    }

    public function accountStatementPdf(Request $request, $id)
    {
        $landlord = User::where('role', 'landlord')->findOrFail($id);
        $filters = $this->accountStatementFilters($request);
        $accountEntries = $this->accountEntriesQuery($landlord->id, $filters)
            ->statementOrder()
            ->get();
        $accountTotals = $this->accountTotalsFor($landlord->id, $filters);
        $period = $this->statementPeriod($accountEntries, $filters);
        $unitStatements = $accountEntries->groupBy(fn (LandlordAccountEntry $entry) => $entry->property_id ?: 'general')
            ->map(function ($entries) {
                $running = 0;
                $entries->each(function (LandlordAccountEntry $entry) use (&$running) {
                    $running += $entry->direction === 'credit' ? (float) $entry->amount : -(float) $entry->amount;
                    $entry->setAttribute('unit_running_balance', $running);
                });
                return ['property' => $entries->first()?->property, 'entries' => $entries, 'balance' => $running];
            });

        return PdfRenderer::downloadView('admin.landlords.pdf.account-statement', compact(
            'landlord',
            'accountEntries',
            'accountTotals',
            'period',
            'unitStatements'
        ), 'owner-statement-' . Str::slug($landlord->name) . '.pdf', ['format' => 'A4-L']);
    }

    public function ownedProperties($id)
    {
        $landlord = User::where('role', 'landlord')->findOrFail($id);
        $relatedProperties = $this->ownerUnitsQuery($landlord->id)
            ->latest()
            ->paginate(12);
        $propertiesTitle = 'Owned Units';
        $detailsRoute = route('admin.landlord.show', $landlord->id);
        $accountStatementRoute = route('admin.landlord.account-statement', $landlord->id);
        $backRoute = route('admin.landlord.index');

        return view('admin.landlords.owned-properties', compact(
            'landlord',
            'relatedProperties',
            'propertiesTitle',
            'detailsRoute',
            'accountStatementRoute',
            'backRoute'
        ));
    }

    public function security($id)
    {
        $landlord = User::where('role', 'landlord')->findOrFail($id);

        return view('admin.landlords.security', [
            'landlord' => $landlord,
            'detailsRoute' => route('admin.landlord.show', $landlord->id),
            'accountStatementRoute' => route('admin.landlord.account-statement', $landlord->id),
            'ownedPropertiesRoute' => route('admin.landlord.owned-properties', $landlord->id),
            'securityRoute' => route('admin.landlord.security', $landlord->id),
        ]);
    }

    public function resetTemporaryPassword(Request $request, $id)
    {
        $validated = $request->validate(['email_credentials' => ['nullable', 'boolean']]);
        $landlord = User::where('role', 'landlord')->findOrFail($id);
        $temporaryPassword = Str::password(12);

        $landlord->forceFill(['password' => Hash::make($temporaryPassword)])->save();

        if ((bool) ($validated['email_credentials'] ?? false)) {
            $this->sendWelcomeEmailAfterResponse($landlord, $temporaryPassword);
        }

        return redirect()->route('admin.landlord.security', $landlord->id)
            ->with('temporary_password', $temporaryPassword)
            ->with('success', 'A new temporary password was generated. It will only be shown once.');
    }



 public function create()
    {
        return view('admin.landlords.create'); // Ensure this Blade file exists
    }

public function store(Request $request)
{
    $validatedData = $request->validate([
        'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        'id_document' => 'nullable|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        'id_document_back' => 'nullable|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        'camera_capture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
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
        'bank_account_type' => 'nullable|string|max:255',
        'swift_code' => 'nullable|string|max:255',
        'iban' => 'nullable|string|max:255',
        'bank_branch' => 'nullable|string|max:255',
        'send_welcome_email' => 'nullable|boolean',
    ]);

    try {
        // Handle file uploads
        $profilePhotoPath = $this->uploadFile($request, 'profile_photo', 'profile_photos');
        $idDocumentPath = $this->uploadFile($request, 'id_document', 'id_documents')
            ?? $this->uploadFile($request, 'camera_capture', 'id_documents');
        $idDocumentBackPath = $this->uploadFile($request, 'id_document_back', 'id_documents');

        $temporaryPassword = Str::password(12);

        // Create the landlord user
        $landlord = User::create([
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
            'bank_account_type' => $validatedData['bank_account_type'] ?? null,
            'swift_code' => $validatedData['swift_code'] ?? null,
            'iban' => $validatedData['iban'] ?? null,
            'bank_branch' => $validatedData['bank_branch'] ?? null,
            'password' => Hash::make($temporaryPassword),
            'role' => 'landlord',
            'profile_photo' => $profilePhotoPath,
            'id_document' => $idDocumentPath,
            'id_document_back' => $idDocumentBackPath,
        ]);

        $this->sendWelcomeEmailAfterResponse($landlord, $temporaryPassword);

        return redirect()->route('admin.landlord.index')
            ->with('success', 'Landlord created successfully!');
    } catch (Throwable $e) {
        Log::error('Error creating landlord.', [
            'error' => $e->getMessage(),
            'email' => $validatedData['email'] ?? null,
            'bank_name' => $validatedData['bank_name'] ?? null,
        ]);

        return redirect()->route('admin.landlord.create')
            ->withErrors(['error' => config('app.debug') ? $e->getMessage() : 'An error occurred while creating the landlord. Please try again.'])
            ->withInput();
    }
}

public function sendWelcomeEmail($id)
{
    $landlord = User::where('role', 'landlord')->findOrFail($id);
    $temporaryPassword = Str::password(12);
    $landlord->forceFill(['password' => Hash::make($temporaryPassword)])->save();

    $this->sendWelcomeEmailAfterResponse($landlord, $temporaryPassword);

    return back()->with('success', 'Welcome email is being sent again.');
}

/**
 * Handle file upload.
 */
private function uploadFile(Request $request, $field, $folder)
{
    if ($request->hasFile($field)) {
        $file = $request->file($field);

        // If multiple files are uploaded, take the first one
        if (is_array($file)) {
            $file = $file[0];
        }

        return MediaStorage::store($file, $folder);
    }

    return null;
}


public function edit($id)
{
    $landlord = User::findOrFail($id);  // Or your landlord model

    return view('admin.landlords.edit', compact('landlord'));
}

public function update(Request $request, $id)
{
    $landlord = User::findOrFail($id);

    $validatedData = $request->validate([
        'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        'id_document' => 'nullable|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        'id_document_back' => 'nullable|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email,' . $landlord->id,
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
        'bank_account_type' => 'nullable|string|max:255',
        'swift_code' => 'nullable|string|max:255',
        'iban' => 'nullable|string|max:255',
        'bank_branch' => 'nullable|string|max:255',
    ]);

    try {
        // Handle file uploads if new files are submitted
        $profilePhotoPath = $this->uploadFile($request, 'profile_photo', 'profile_photos');
        $idDocumentPath = $this->uploadFile($request, 'id_document', 'id_documents');
        $idDocumentBackPath = $this->uploadFile($request, 'id_document_back', 'id_documents');

        // Update landlord fields
        $landlord->update([
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
            'bank_account_type' => $validatedData['bank_account_type'] ?? null,
            'swift_code' => $validatedData['swift_code'] ?? null,
            'iban' => $validatedData['iban'] ?? null,
            'bank_branch' => $validatedData['bank_branch'] ?? null,
            'profile_photo' => $profilePhotoPath ?? $landlord->profile_photo,
            'id_document' => $idDocumentPath ?? $landlord->id_document,
            'id_document_back' => $idDocumentBackPath ?? $landlord->id_document_back,
        ]);

        return redirect()->route('admin.landlord.index')
            ->with('success', 'Landlord updated successfully!');
    } catch (\Exception $e) {
        Log::error('Error updating landlord: ' . $e->getMessage());

        return redirect()->route('admin.landlord.edit', $landlord->id)
            ->withErrors(['error' => 'An error occurred while updating the landlord. Please try again.'])
            ->withInput();
    }
}

        public function genratePdf()
        {
            // Fetch all landlords
            $landlords = User::where('role', 'landlord')->get();
            $totalLandlords = $landlords->count();

            return PdfRenderer::downloadView('admin.pdf.landlords.list', compact('landlords', 'totalLandlords'), 'landlords_list.pdf');
        }


        public function destroy($id)
{
    try {
        $landlord = User::where('role', 'landlord')->findOrFail($id);

        $landlord->delete(); // Soft delete

        return redirect()->route('admin.landlord.index')->with('success', 'Landlord deleted successfully.');
    } catch (\Exception $e) {
        Log::error('Error deleting landlord: ' . $e->getMessage());

        return redirect()->route('admin.landlord.index')->withErrors(['error' => 'Failed to delete landlord.']);
    }
}

public function updateBankDetails(Request $request, $id)
{
    // Find the landlord by ID
    $landlord = User::findOrFail($id);

    // Validate the bank details fields
    $validatedData = $request->validate([
        'bank_name' => 'nullable|string|max:255',
        'bank_account_holder' => 'nullable|string|max:255',
        'bank_account_number' => 'nullable|string|max:255',
        'bank_account_type' => 'nullable|string|max:255',
        'swift_code' => 'nullable|string|max:255',
        'iban' => 'nullable|string|max:255',
        'bank_branch' => 'nullable|string|max:255',
    ]);

    try {
        // Update the bank details
        $landlord->update([
            'bank_name' => $request->bank_name,
            'bank_account_holder' => $request->bank_account_holder,
            'bank_account_number' => $request->bank_account_number,
            'bank_account_type' => $request->bank_account_type,
            'swift_code' => $request->swift_code,
            'iban' => $request->iban,
            'bank_branch' => $request->bank_branch,
        ]);



        return back()
                         ->with('success', 'Bank details updated successfully!');
    } catch (\Exception $e) {
        // Log the error if there's an issue
        Log::error('Error updating bank details: ' . $e->getMessage());

        return redirect()->route('admin.landlord.show', $landlord->id)
                         ->withErrors(['error' => 'Failed to update bank details. Please try again.']);
    }
}

public function storeAccountEntry(Request $request, $id)
{
    $landlord = User::where('role', 'landlord')->findOrFail($id);
    $validatedData = $request->validate([
        'entry_date' => 'required|date',
        'type' => 'required|string|max:100',
        'custom_type' => 'nullable|required_if:type,__custom__|string|max:100',
        'custom_direction' => 'nullable|required_if:type,__custom__|in:credit,debit',
        'amount' => 'required|numeric|min:0.01',
        'property_id' => 'nullable|exists:properties,id',
        'reference' => 'nullable|string|max:255',
        'description' => 'nullable|string|max:1000',
        'invoice_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        'receipt_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        'redirect_to' => 'nullable|string|max:2048',
    ]);

    $propertyId = $validatedData['property_id'] ?? null;

    if ($propertyId && ! $this->ownerUnitsQuery($landlord->id)->where('id', $propertyId)->exists()) {
        return back()
            ->withErrors(['property_id' => 'Please select one of this owner units.']);
    }

    $entryTypes = LandlordAccountEntry::allTypes();
    $type = $validatedData['type'] === '__custom__'
        ? Str::slug($validatedData['custom_type'], '_')
        : $validatedData['type'];

    if ($validatedData['type'] !== '__custom__' && ! array_key_exists($type, $entryTypes)
        && ! LandlordAccountEntry::where('type', $type)->exists()) {
        return back()->withErrors(['type' => 'Please select a valid statement category.'])->withInput();
    }

    LandlordAccountEntry::create([
        'landlord_id' => $landlord->id,
        'property_id' => $propertyId,
        'entry_date' => $validatedData['entry_date'],
        'type' => $type,
        'direction' => $validatedData['type'] === '__custom__'
            ? $validatedData['custom_direction']
            : LandlordAccountEntry::directionForType($type),
        'amount' => $validatedData['amount'],
        'reference' => $validatedData['reference'] ?? null,
        'description' => $validatedData['description'] ?? null,
        'invoice_attachment' => $this->uploadFile($request, 'invoice_attachment', 'owner_statement_invoices'),
        'receipt_attachment' => $this->uploadFile($request, 'receipt_attachment', 'owner_statement_receipts'),
    ]);

    LandlordAccountEntry::recalculateBalancesFor($landlord->id);

    $redirectTo = $validatedData['redirect_to'] ?? null;
    $fallbackUrl = route('admin.landlord.account-statement', $landlord->id);

    if (! $redirectTo || ! str_starts_with($redirectTo, url('/'))) {
        $redirectTo = $fallbackUrl;
    }

    return redirect()->to($redirectTo)
        ->with('success', 'Owner account statement entry added successfully.');
}

private function accountTotalsFor(string $landlordId, array $filters = []): array
{
    $query = $this->accountEntriesQuery($landlordId, $filters);
    $totals = [
        'credit' => (float) (clone $query)->where('direction', 'credit')->sum('amount'),
        'debit' => (float) (clone $query)->where('direction', 'debit')->sum('amount'),
    ];
    $totals['balance'] = $totals['credit'] - $totals['debit'];

    return $totals;
}

private function accountEntriesQuery(string $landlordId, array $filters = [])
{
    return LandlordAccountEntry::with('property.building')
        ->where('landlord_id', $landlordId)
        ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('entry_date', '>=', $filters['date_from']))
        ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('entry_date', '<=', $filters['date_to']))
        ->when(! empty($filters['property_id']), fn ($query) => $query->where('property_id', $filters['property_id']));
}

private function accountStatementFilters(Request $request): array
{
    return [
        'date_from' => $request->input('date_from'),
        'date_to' => $request->input('date_to'),
        'property_id' => $request->input('property_id'),
    ];
}

private function ownerUnitsQuery(string $landlordId)
{
    return Property::with(['building', 'ownerShares.owner'])
        ->where(function ($query) use ($landlordId) {
            $query->where('landlord_id', $landlordId)
                ->orWhereHas('ownerShares', fn ($shareQuery) => $shareQuery->where('owner_id', $landlordId));
        });
}

private function attachOwnerUnitSummary($landlords): void
{
    $balances = LandlordAccountEntry::query()
        ->whereIn('landlord_id', $landlords->pluck('id'))
        ->selectRaw("landlord_id, SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END) as account_balance")
        ->groupBy('landlord_id')
        ->pluck('account_balance', 'landlord_id');

    $landlords->each(function (User $landlord) {
        $units = $this->ownerUnitsQuery($landlord->id)->get();
        $landlord->setAttribute('owned_units_count', $units->count());
        $landlord->setAttribute('booked_units_count', $units->whereIn('status', ['booked', 'rented'])->count());
        $landlord->setAttribute('available_units_count', $units->whereIn('status', ['available', 'vacant'])->count());
        $landlord->setRelation('owned_units_preview', $units->take(3));
    });

    $landlords->each(function (User $landlord) use ($balances) {
        $landlord->setAttribute('account_balance', (float) ($balances[$landlord->id] ?? 0));
    });
}

private function statementPeriod($accountEntries, array $filters): array
{
    $firstDate = $filters['date_from'] ?: optional($accountEntries->first()?->entry_date)->toDateString();
    $lastDate = $filters['date_to'] ?: optional($accountEntries->last()?->entry_date)->toDateString();

    return [
        'from' => $firstDate ? \Carbon\Carbon::parse($firstDate) : now(),
        'to' => $lastDate ? \Carbon\Carbon::parse($lastDate) : now(),
    ];
}

private function sendWelcomeEmailAfterResponse(User $landlord, string $temporaryPassword): void
{
    app()->terminating(function () use ($landlord, $temporaryPassword) {
        try {
            Notification::send($landlord, new LandlordCreated($landlord, $temporaryPassword));
        } catch (Throwable $mailException) {
            Log::warning('Landlord welcome email failed.', [
                'landlord_id' => $landlord->id,
                'email' => $landlord->email,
                'error' => $mailException->getMessage(),
            ]);
        }
    });
}

}

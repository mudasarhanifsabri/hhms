<?php

namespace App\Http\Controllers\admin\properties;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\UtilityAccount;
use App\Models\User;
use App\Models\Building;
use App\Models\Smartlock;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $search = $request->input('q');

        $properties = $this->unitListQuery($status, $search)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $unitStats = $this->unitStats();

        return view('admin.properties.index', compact('properties', 'unitStats', 'status', 'search'));
    }

    public function showGrid(Request $request)
    {
        $status = $request->input('status');
        $search = $request->input('q');

        $properties = $this->unitListQuery($status, $search)
            ->latest()
            ->paginate(12)
            ->withQueryString();
        $unitStats = $this->unitStats();

        return view('admin.properties.showgrid', compact('properties', 'unitStats', 'status', 'search'));
    }

    private function unitListQuery(?string $status = null, ?string $search = null)
    {
        return Property::with(['building', 'landlord', 'ownerShares.owner'])
            ->when($status, function ($query) use ($status) {
                $statuses = match ($status) {
                    'available' => ['available', 'vacant'],
                    'booked' => ['booked', 'rented'],
                    default => [$status],
                };

                $query->whereIn('status', $statuses);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('community', 'like', "%{$search}%")
                        ->orWhereHas('landlord', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('building', function ($query) use ($search) {
                            $query->where('building_name', 'like', "%{$search}%")
                                ->orWhere('address', 'like', "%{$search}%");
                        });
                });
            });
    }

    private function unitStats(): array
    {
        return [
            'total' => Property::count(),
            'available' => Property::whereIn('status', ['available', 'vacant'])->count(),
            'booked' => Property::whereIn('status', ['booked', 'rented'])->count(),
            'attention' => Property::whereIn('status', ['under_cleaning', 'under_maintenance'])->count(),
        ];
    }

    public function create()
    {
        $landlords = User::where('role', 'landlord')->get();
        $buildings = Building::all();

        return view('admin.properties.create', compact('landlords', 'buildings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'landlord_id' => 'required|exists:users,id',
            'owner_ids' => 'nullable|array',
            'owner_ids.*' => 'nullable|exists:users,id',
            'owner_shares' => 'nullable|array',
            'owner_shares.*' => 'nullable|numeric|min:0|max:100',
            'building_id' => 'required|exists:buildings,id',
            'smartlock_id' => 'nullable|exists:smartlocks,id',

            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'community' => 'nullable|string|max:255',
            'rent' => 'nullable|numeric',
            'management_fee' => 'nullable|numeric',
            'management_fee_percent' => 'nullable|numeric|min:0|max:100',
            'bedrooms' => 'nullable|integer',
            'bathrooms' => 'nullable|integer',
            'living_rooms' => 'nullable|integer',
            'kitchens' => 'nullable|integer',
            'square_foot' => 'nullable|integer',
            'floor' => 'nullable|integer',
            'room_no' => 'nullable|string|max:255',
            'unit_floor_label' => 'nullable|string|max:255',
            'parking_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',

            'amenities' => 'nullable|array',
            'has_security' => 'nullable|string', // handled as 'Yes' or 'No'
            'security_utilities' => 'nullable|array',
            'additional_features' => 'nullable|array',
            'distance_to_road' => 'nullable|string|max:255',
            'additional_notes' => 'nullable|string',

            'video' => 'nullable|file|mimes:mp4,avi,mov,pdf|max:102400',
            'floor_plan' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',

            'dtcm_unit_permit' => 'nullable|file|mimes:pdf,jpg,png|max:10240',
            'title_deed' => 'nullable|file|mimes:pdf,jpg,png|max:10240',
            'dtcm_permit_no' => 'nullable|string|max:255',
            'dtcm_permit_expiry' => 'nullable|date',

            'wifi_provider' => 'nullable|string|max:255',
            'wifi_name' => 'nullable|string|max:255',
            'wifi_account_no' => 'nullable|string|max:255',
            'wifi_password' => 'nullable|string|max:255',
            'utilities_cap' => 'nullable|numeric|min:0',
            'electricity_provider' => 'nullable|string|max:255',
            'electricity_account_no' => 'nullable|string|max:255',
            'utility_accounts' => 'nullable|array',
            'utility_accounts.*.enabled' => 'nullable|boolean',
            'utility_accounts.*.responsibility' => 'nullable|in:owner,company,tenant_guest',
            'utility_accounts.*.supplier' => 'nullable|string|max:255',
            'utility_accounts.*.account_number' => 'nullable|string|max:255',
            'utility_accounts.*.username' => 'nullable|string|max:255',
            'utility_accounts.*.portal_password' => 'nullable|string|max:255',
            'utility_accounts.*.contract_number' => 'nullable|string|max:255',
            'utility_accounts.*.connection_status' => 'nullable|string|max:50',
            'utility_accounts.*.connection_start_date' => 'nullable|date',
            'utility_accounts.*.contract_expiry_date' => 'nullable|date',
            'utility_accounts.*.billing_day' => 'nullable|integer|min:1|max:31',
            'utility_accounts.*.notes' => 'nullable|string|max:1000',
        ]);

        unset($validated['utility_accounts']);
        $this->applyUnitTypeDefaults($validated);

        // Handle file uploads using the configured HHMS media disk.
        if ($request->hasFile('dtcm_unit_permit')) {
            $validated['dtcm_unit_permit'] = MediaStorage::store($request->file('dtcm_unit_permit'), 'documents');
        }

        if ($request->hasFile('title_deed')) {
            $validated['title_deed'] = MediaStorage::store($request->file('title_deed'), 'documents');
        }

        if ($request->hasFile('video')) {
            $validated['video'] = MediaStorage::store($request->file('video'), 'videos');
        }

        if ($request->hasFile('floor_plan')) {
            $validated['floor_plan'] = MediaStorage::store($request->file('floor_plan'), 'floor_plans');
        }

        // Handle multiple photo uploads
        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photos[] = MediaStorage::store($photo, 'property_photos');
            }
            $validated['photos'] = $photos;
        }

        // Ensure UUID for unit ID
        $validated['id'] = Str::uuid()->toString();

        $property = Property::create($validated);
        $this->syncOwnerShares($property, $request);
        $this->syncUtilityAccounts($property, $request);

        return redirect()->route('admin.property.index')->with('success', 'Unit created successfully.');
    }

    public function show(Property $property)
    {
        $property->load(['landlord', 'building', 'ownerShares.owner']);

        return view('admin.properties.show', compact('property'));
    }

    public function edit(Property $property)
    {
        $property->load(['ownerShares.owner', 'utilityAccounts']);
        $landlords = User::where('role', 'landlord')->get();
        $buildings = Building::all();

        return view('admin.properties.edit', compact('property', 'landlords', 'buildings'));
    }

    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'landlord_id' => 'required|exists:users,id',
            'owner_ids' => 'nullable|array',
            'owner_ids.*' => 'nullable|exists:users,id',
            'owner_shares' => 'nullable|array',
            'owner_shares.*' => 'nullable|numeric|min:0|max:100',
            'building_id' => 'required|exists:buildings,id',
            'name' => 'required|string|max:255',
            'status' => 'required|in:available,booked,under_cleaning,under_maintenance',
            'category' => 'nullable|string|max:255',
            'community' => 'nullable|string|max:255',
            'rent' => 'nullable|numeric',
            'management_fee_percent' => 'nullable|numeric|min:0|max:100',
            'utilities_cap' => 'nullable|numeric|min:0',
            'room_no' => 'nullable|string|max:255',
            'unit_floor_label' => 'nullable|string|max:255',
            'parking_number' => 'nullable|string|max:255',
            'wifi_name' => 'nullable|string|max:255',
            'wifi_password' => 'nullable|string|max:255',
            'dtcm_permit_expiry' => 'nullable|date',
            'description' => 'nullable|string',
            'utility_accounts' => 'nullable|array',
            'utility_accounts.*.enabled' => 'nullable|boolean',
            'utility_accounts.*.responsibility' => 'nullable|in:owner,company,tenant_guest',
            'utility_accounts.*.supplier' => 'nullable|string|max:255',
            'utility_accounts.*.account_number' => 'nullable|string|max:255',
            'utility_accounts.*.username' => 'nullable|string|max:255',
            'utility_accounts.*.portal_password' => 'nullable|string|max:255',
            'utility_accounts.*.contract_number' => 'nullable|string|max:255',
            'utility_accounts.*.connection_status' => 'nullable|string|max:50',
            'utility_accounts.*.connection_start_date' => 'nullable|date',
            'utility_accounts.*.contract_expiry_date' => 'nullable|date',
            'utility_accounts.*.billing_day' => 'nullable|integer|min:1|max:31',
            'utility_accounts.*.notes' => 'nullable|string|max:1000',
        ]);

        unset($validated['utility_accounts']);
        $this->applyUnitTypeDefaults($validated);

        $property->update($validated);
        $this->syncOwnerShares($property, $request);
        $this->syncUtilityAccounts($property, $request);

        return redirect()->route('admin.property.index')->with('success', 'Unit updated successfully.');
    }

    public function destroy(Property $property)
    {
        $property->delete();
        return redirect()->back()->with('success', 'Unit deleted successfully.');
    }

    private function syncOwnerShares(Property $property, Request $request): void
    {
        $ownerIds = $request->input('owner_ids', []);
        $shares = $request->input('owner_shares', []);
        $sync = [];

        foreach ($ownerIds as $index => $ownerId) {
            if (! $ownerId) {
                continue;
            }

            $sync[$ownerId] = [
                'share_percent' => isset($shares[$index]) && $shares[$index] !== null && $shares[$index] !== ''
                    ? (float) $shares[$index]
                    : 0,
                'is_primary' => $ownerId === $property->landlord_id,
            ];
        }

        if (! array_key_exists($property->landlord_id, $sync)) {
            $sync[$property->landlord_id] = [
                'share_percent' => empty($sync) ? 100 : 0,
                'is_primary' => true,
            ];
        }

        $property->owners()->sync($sync);
    }

    private function syncUtilityAccounts(Property $property, Request $request): void
    {
        foreach ($request->input('utility_accounts', []) as $type => $payload) {
            if (! array_key_exists($type, UtilityAccount::TYPES)) {
                continue;
            }

            $enabled = (bool) ($payload['enabled'] ?? false);
            $hasDetails = collect($payload)
                ->except(['enabled', 'portal_password'])
                ->filter(fn ($value) => filled($value))
                ->isNotEmpty();

            if (! $enabled && ! $hasDetails) {
                continue;
            }

            $account = UtilityAccount::updateOrCreate(
                ['property_id' => $property->id, 'utility_type' => $type],
                [
                    'responsibility' => $payload['responsibility'] ?? 'company',
                    'supplier' => $payload['supplier'] ?? null,
                    'account_number' => $payload['account_number'] ?? null,
                    'username' => $payload['username'] ?? null,
                    'contract_number' => $payload['contract_number'] ?? null,
                    'connection_status' => $payload['connection_status'] ?? 'active',
                    'connection_start_date' => $payload['connection_start_date'] ?? null,
                    'contract_expiry_date' => $payload['contract_expiry_date'] ?? null,
                    'billing_day' => $payload['billing_day'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                ]
            );

            if (filled($payload['portal_password'] ?? null)) {
                $account->portal_password = $payload['portal_password'];
                $account->save();
            }
        }
    }

    private function applyUnitTypeDefaults(array &$validated): void
    {
        $unitType = $validated['category'] ?? null;

        if ($unitType && array_key_exists($unitType, Property::UNIT_TYPES)) {
            $validated['bedrooms'] = Property::UNIT_TYPES[$unitType];
            $validated['living_rooms'] = 1;
            $validated['kitchens'] = 1;
            $validated['room_no'] = $unitType;
        }
    }
}

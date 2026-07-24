<?php

namespace App\Http\Controllers\admin\properties;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\User;
use App\Models\Building;
use App\Models\Smartlock;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function index()
    {
        $properties = Property::latest()->paginate(10);
        return view('admin.properties.index', compact('properties'));
    }

    public function showGrid()
    {
        $properties = Property::latest()->paginate(12);
        return view('admin.properties.showgrid', compact('properties'));
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
        ]);

        // Handle file uploads (store in /storage/app/public)
        if ($request->hasFile('dtcm_unit_permit')) {
            $validated['dtcm_unit_permit'] = $request->file('dtcm_unit_permit')->store('documents', 'public');
        }

        if ($request->hasFile('title_deed')) {
            $validated['title_deed'] = $request->file('title_deed')->store('documents', 'public');
        }

        if ($request->hasFile('video')) {
            $validated['video'] = $request->file('video')->store('videos', 'public');
        }

        if ($request->hasFile('floor_plan')) {
            $validated['floor_plan'] = $request->file('floor_plan')->store('floor_plans', 'public');
        }

        // Handle multiple photo uploads
        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photos[] = $photo->store('property_photos', 'public');
            }
            $validated['photos'] = $photos;
        }

        // Ensure UUID for unit ID
        $validated['id'] = Str::uuid()->toString();

        $property = Property::create($validated);
        $this->syncOwnerShares($property, $request);

        return redirect()->route('admin.property.index')->with('success', 'Unit created successfully.');
    }

    public function show(Property $property)
    {
        $property->load(['landlord', 'building', 'ownerShares.owner']);

        return view('admin.properties.show', compact('property'));
    }

    public function edit(Property $property)
    {
        $property->load('ownerShares.owner');
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
        ]);

        $property->update($validated);
        $this->syncOwnerShares($property, $request);

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
}

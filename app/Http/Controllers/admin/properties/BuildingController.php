<?php

namespace App\Http\Controllers\Admin\Properties;

use App\Http\Controllers\Controller;


use App\Models\Building;
use Illuminate\Http\Request;

class BuildingController extends Controller
{


  public function index(Request $request)
{
    $query = Building::query();

    if ($request->filled('search')) {
        $query->where('building_name', 'like', '%' . $request->search . '%');
    }

    $perPage = $request->input('per_page', 5);
    $buildings = $query->paginate($perPage)->appends($request->all());

    return view('admin.properties.buildings.index', compact('buildings'));
}


public function store(Request $request)
{
    $validated = $request->validate([
        'building_name' => 'required|string|max:255',
        'management_email' => 'nullable|email',
        'security_contact' => 'nullable|string',
        'gas_provider' => 'nullable|string',
        'address' => 'required|string',
        'city' => 'nullable|string',
        'state' => 'nullable|string',
        'country' => 'nullable|string',
        'google_map_link' => 'nullable|string',
        'year_built' => 'nullable|integer',
    ]);

    $building = Building::create($validated);

    return redirect()->route('admin.building.index')
        ->with('success', 'Building created successfully.');
}

    public function byLandlord($landlord_id)
    {
        $buildings = Building::where('landlord_id', $landlord_id)->get();
        return response()->json($buildings);
    }
}

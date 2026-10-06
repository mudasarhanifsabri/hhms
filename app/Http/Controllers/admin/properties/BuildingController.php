<?php

namespace App\Http\Controllers\admin\properties;

use App\Http\Controllers\Controller;


use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BuildingController extends Controller
{


  public function index(Request $request)
{
    $query = Building::query()->withCount(['properties as unit_count']);

    if ($request->filled('search')) {
        $search = trim((string) $request->search);
        $query->where(function ($query) use ($search) {
            $query->where('building_name', 'like', '%'.$search.'%')
                ->orWhere('management_email', 'like', '%'.$search.'%')
                ->orWhere('city', 'like', '%'.$search.'%')
                ->orWhere('address', 'like', '%'.$search.'%');
        });
    }

    $perPage = in_array((int) $request->input('per_page', 10), [5, 10, 25, 50, 100], true)
        ? (int) $request->input('per_page', 10)
        : 10;
    $buildings = $query->paginate($perPage)->appends($request->all());

    return view('admin.properties.buildings.index', compact('buildings'));
}


public function store(Request $request)
{
    $validated = $request->validate($this->rules());

    Building::create($validated);

    return redirect()->route('admin.building.index')
        ->with('success', 'Building created successfully.');
}

    public function show(Building $building)
    {
        $building->loadCount('properties')->load(['properties' => fn ($query) => $query->orderBy('name')]);

        return view('admin.properties.buildings.show', compact('building'));
    }

    public function edit(Building $building)
    {
        return view('admin.properties.buildings.edit', compact('building'));
    }

    public function update(Request $request, Building $building)
    {
        $building->update($request->validate($this->rules($building)));

        return redirect()->route('admin.building.index')->with('success', 'Building updated successfully.');
    }

    public function destroy(Building $building)
    {
        $unitCount = $building->properties()->count();
        if ($unitCount > 0) {
            return back()->withErrors(['building' => 'This building cannot be deleted because it has '.$unitCount.' linked unit'.($unitCount === 1 ? '' : 's').'. Move or delete those units first.']);
        }

        $name = $building->building_name;
        $building->delete();

        return redirect()->route('admin.building.index')->with('success', $name.' deleted successfully.');
    }

    public function byLandlord($landlord_id)
    {
        $buildings = Building::whereHas('properties', fn ($query) => $query->where('landlord_id', $landlord_id))
            ->orderBy('building_name')
            ->get();
        return response()->json($buildings);
    }

    private function rules(?Building $building = null): array
    {
        return [
            'building_name' => ['required', 'string', 'max:255', Rule::unique('buildings', 'building_name')->ignore($building?->id)],
            'management_email' => ['nullable', 'email:rfc', 'max:255'],
            'security_contact' => ['nullable', 'string', 'max:255'],
            'gas_provider' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'google_map_link' => ['nullable', 'url:http,https', 'max:2000'],
            'year_built' => ['nullable', 'integer', 'min:1800', 'max:'.now()->year],
        ];
    }
}

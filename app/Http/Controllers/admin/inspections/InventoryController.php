<?php

namespace App\Http\Controllers\admin\inspections;

use App\Http\Controllers\Controller;
use App\Models\BookingInspection;
use App\Models\Property;
use App\Models\UnitInventoryItem;
use App\Models\User;
use App\Support\UnitInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $properties = Property::with('building')->orderBy('name')->get();
        $property = $request->filled('property_id') ? Property::findOrFail($request->property_id) : $properties->first();
        $items = $property ? UnitInventoryItem::where('property_id', $property->id)->orderBy('room')->orderBy('name')->get() : collect();
        $movements = DB::table('unit_inventory_movements as m')->join('unit_inventory_items as i', 'i.id', '=', 'm.item_id')->leftJoin('users as u', 'u.id', '=', 'm.user_id')
            ->where('i.property_id', $property?->id)->select('m.*', 'i.name', 'u.name as actor')->orderByDesc('m.created_at')->paginate(15)->withQueryString();
        $maintainers = User::where('role', 'maintainer')->where('is_active', true)->orderBy('name')->get();
        $bookings = \App\Models\Booking::where('property_id', $property?->id)->latest()->get();
        $templates = DB::table('unit_inventory_templates')->orderBy('name')->get();
        $summaries = UnitInventoryItem::selectRaw('property_id, COUNT(*) as item_types, SUM(required) as required_total, SUM(present) as present_total, SUM(damaged) as damaged_total, SUM(CASE WHEN required > present THEN required - present ELSE 0 END) as missing_total')->groupBy('property_id')->get()->keyBy('property_id');

        return view('admin.inspections.inventory', compact('properties', 'property', 'items', 'movements', 'maintainers', 'bookings', 'templates', 'summaries'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['property_id' => 'required|exists:properties,id', 'name' => 'required|string|max:150', 'room' => 'required|string|max:100', 'required' => 'required|integer|min:0|max:100000', 'replacement_cost' => 'required|numeric|min:0|max:99999999|decimal:0,2']);
        Property::findOrFail($data['property_id']);
        if (UnitInventoryItem::where('property_id', $data['property_id'])->where('room', $data['room'])->where('name', $data['name'])->exists()) {
            return back()->withErrors(['name' => 'This room already has that item.']);
        }
        UnitInventoryItem::create($data);

        return back()->with('success', 'Required item added. Record receipt or approve an inspection to establish actual stock.');
    }

    public function template(Request $request)
    {
        $data = $request->validate(['property_id' => 'required|exists:properties,id', 'action' => 'required|in:save,apply', 'name' => 'nullable|required_if:action,save|string|max:100|unique:unit_inventory_templates,name', 'template_id' => 'nullable|required_if:action,apply|exists:unit_inventory_templates,id']);
        DB::transaction(function () use ($data) {
            Property::whereKey($data['property_id'])->lockForUpdate()->firstOrFail();
            if ($data['action'] === 'save') {
                $rows = UnitInventoryItem::where('property_id', $data['property_id'])->get(['room', 'name', 'required', 'replacement_cost']);
                if ($rows->isEmpty()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Add required items before saving a template.']);
                }
                DB::table('unit_inventory_templates')->insert(['id' => (string) \Illuminate\Support\Str::uuid(), 'name' => $data['name'], 'rows' => $rows->toJson(), 'created_at' => now(), 'updated_at' => now()]);
            } else {
                $template = DB::table('unit_inventory_templates')->where('id', $data['template_id'])->first();
                foreach (json_decode($template->rows, true) as $row) {
                    UnitInventoryItem::firstOrCreate(['property_id' => $data['property_id'], 'name' => $row['name'], 'room' => $row['room']], ['required' => $row['required'], 'replacement_cost' => $row['replacement_cost']]);
                }
            }
        });

        return back()->with('success', 'Template saved/applied. Existing items and actual quantities were not overwritten.');
    }

    public function update(Request $request, UnitInventoryItem $item)
    {
        $data = $request->validate(['required' => 'required|integer|min:0|max:100000', 'replacement_cost' => 'required|numeric|min:0|max:99999999|decimal:0,2']);
        DB::transaction(function () use ($item, $data) {
            $item = UnitInventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            UnitInventory::movement($item, 0, 0, 'requirements', 'Requirements/cost changed: '.json_encode(['before' => $item->only(['required', 'replacement_cost']), 'after' => $data]));
            $item->update($data);
        });

        return back()->with('success', 'Requirements updated; actual stock unchanged.');
    }

    public function move(Request $request, UnitInventoryItem $item)
    {
        $data = $request->validate(['type' => 'required|in:receive,dispose,repair,transfer', 'quantity' => 'required|integer|min:1|max:100000', 'reason' => 'required|string|min:5|max:1000', 'target_property_id' => 'nullable|required_if:type,transfer|exists:properties,id']);
        DB::transaction(function () use ($item, $data) {
            // Global inventory movement lock order starts with properties.
            Property::whereIn('id', array_filter([$item->property_id, $data['target_property_id'] ?? null]))->orderBy('id')->lockForUpdate()->get();
            $item = UnitInventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $q = (int) $data['quantity'];
            if ($data['type'] === 'transfer') {
                if ($data['target_property_id'] === $item->property_id) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['target_property_id' => 'Choose a different unit.']);
                }
                if ($q > $item->present - $item->damaged) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['quantity' => 'Only available undamaged stock can be transferred.']);
                }
                $target = UnitInventoryItem::firstOrCreate(['property_id' => $data['target_property_id'], 'room' => $item->room, 'name' => $item->name], ['required' => 0, 'replacement_cost' => $item->replacement_cost]);
                UnitInventory::movement($target, $q, 0, 'transfer_in', $data['reason'].' / From unit '.$item->property_id);
                UnitInventory::movement($item, -$q, 0, 'transfer_out', $data['reason'].' / To unit '.$target->property_id);
            } else {
                $delta = match ($data['type']) {
                    'receive' => $q, 'dispose' => -$q, default => 0
                };
                $damage = in_array($data['type'], ['dispose', 'repair']) ? -$q : 0;
                UnitInventory::movement($item, $delta, $damage, $data['type'], $data['reason']);
            }
        });

        return back()->with('success', 'Stock movement recorded.');
    }

    public function requestInspection(Request $request)
    {
        $data = $request->validate(['property_id' => 'required|exists:properties,id', 'booking_id' => 'nullable|required_if:inspection_type,check_in,check_out|exists:bookings,id', 'assigned_to' => 'required|exists:users,id,role,maintainer', 'inspection_type' => 'required|in:check_in,check_out,routine,maintenance,cleaning', 'due_date' => 'required|date', 'description' => 'required|string|max:3000']);
        Property::findOrFail($data['property_id']);
        if (! empty($data['booking_id'])) {
            \App\Models\Booking::where('property_id', $data['property_id'])->findOrFail($data['booking_id']);
        }
        User::where('role', 'maintainer')->where('is_active', true)->findOrFail($data['assigned_to']);
        $request->replace($data + ['type' => 'inspection', 'priority' => 'medium', 'title' => 'Office requested '.ucfirst($data['inspection_type']).' inspection']);

        return DB::transaction(fn () => app(\App\Http\Controllers\admin\tasks\TaskController::class)->store($request));
    }

    public function approve(Request $request, BookingInspection $inspection)
    {
        $data = $request->validate(['notes' => 'required|string|min:5|max:2000', 'create_task' => 'nullable|boolean']);
        UnitInventory::approve($inspection, $data['notes'], $request->boolean('create_task'));

        return back()->with('success', 'Inventory approved. No guest charges or accounting entries were created.');
    }
}

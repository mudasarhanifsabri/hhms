<?php

namespace App\Support;

use App\Models\BookingInspection;
use App\Models\UnitInventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UnitInventory
{
    public static function snapshot(BookingInspection $inspection): array
    {
        return DB::transaction(function () use ($inspection) {
            BookingInspection::whereKey($inspection->id)->lockForUpdate()->firstOrFail();
            $existing = DB::table('unit_inventory_reviews')->where('inspection_id', $inspection->id)->first();
            if ($existing) {
                return json_decode($existing->rows, true);
            }
            $rows = UnitInventoryItem::where('property_id', $inspection->property_id)->orderBy('room')->orderBy('name')->get()->map(fn ($i) => [
                'id' => $i->id, 'name' => $i->name, 'room' => $i->room, 'required' => $i->required,
                'before' => $i->present, 'before_damaged' => $i->damaged, 'version' => $i->version,
                'cost' => (float) $i->replacement_cost, 'found' => null, 'damaged' => null,
            ])->all();
            DB::table('unit_inventory_reviews')->insert(['id' => (string) Str::uuid(), 'inspection_id' => $inspection->id, 'rows' => json_encode($rows), 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);

            return $rows;
        });
    }

    public static function submit(BookingInspection $inspection, array $input): void
    {
        $rows = self::snapshot($inspection);
        foreach ($rows as &$row) {
            $values = $input[$row['id']] ?? [];
            validator($values, ['found' => 'required|integer|min:0|max:100000', 'damaged' => 'required|integer|min:0|lte:found', 'notes' => 'nullable|string|max:1000'])->validate();
            $row['found'] = (int) $values['found'];
            $row['damaged'] = (int) $values['damaged'];
            $row['notes'] = $values['notes'] ?? '';
            $row['missing'] = max(0, $row['required'] - $row['found']);
        }
        unset($row);
        DB::table('unit_inventory_reviews')->where('inspection_id', $inspection->id)->where('status', 'draft')->update(['rows' => json_encode($rows), 'status' => 'submitted', 'updated_at' => now()]);
    }

    public static function assessment(BookingInspection $inspection, array $rows): array
    {
        $baseline = null;
        if ($inspection->type === 'check_out' && $inspection->booking_id) {
            $baseline = DB::table('unit_inventory_reviews as r')->join('booking_inspections as i', 'i.id', '=', 'r.inspection_id')
                ->where('i.booking_id', $inspection->booking_id)->where('i.type', 'check_in')->where('r.status', 'approved')
                ->where('i.created_at', '<=', $inspection->created_at)->orderByDesc('i.created_at')->select('r.rows')->first();
        }
        $before = collect($baseline ? json_decode($baseline->rows, true) : [])->keyBy('id');

        return array_map(function ($row) use ($before) {
            $base = $before->get($row['id']);
            $row['new_missing'] = $base ? max(0, $base['found'] - $row['found']) : null;
            $row['new_damaged'] = $base ? max(0, $row['damaged'] - $base['damaged']) : null;
            $row['estimate'] = $base ? round(($row['new_missing'] + $row['new_damaged']) * $row['cost'], 2) : null;

            return $row;
        }, $rows);
    }

    public static function approve(BookingInspection $inspection, string $notes, bool $createTask = false): void
    {
        DB::transaction(function () use ($inspection, $notes, $createTask) {
            BookingInspection::whereKey($inspection->id)->lockForUpdate()->firstOrFail();
            $review = DB::table('unit_inventory_reviews')->where('inspection_id', $inspection->id)->lockForUpdate()->first();
            if (! $review || $review->status !== 'submitted') {
                throw ValidationException::withMessages(['inventory' => 'Only submitted inventory counts can be approved.']);
            }
            $rows = self::assessment($inspection, json_decode($review->rows, true));
            foreach (collect($rows)->sortBy('id') as $row) {
                $item = UnitInventoryItem::whereKey($row['id'])->lockForUpdate()->firstOrFail();
                if ($item->version !== $row['version']) {
                    throw ValidationException::withMessages(['inventory' => 'Stock changed since this inspection started. Request a fresh inspection; this report remains unchanged.']);
                }
                self::movement($item, $row['found'] - $item->present, $row['damaged'] - $item->damaged, 'inspection', $notes, $inspection->id);
            }
            DB::table('unit_inventory_reviews')->where('id', $review->id)->update(['rows' => json_encode($rows), 'status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'notes' => $notes, 'updated_at' => now()]);
            $issues = collect($rows)->filter(fn ($r) => $r['missing'] > 0 || $r['damaged'] > 0);
            if ($createTask && $issues->isNotEmpty()) {
                $task = \App\Models\BookingTask::create(['task_number' => 'INV-'.Str::upper(Str::random(12)), 'booking_id' => $inspection->booking_id, 'property_id' => $inspection->property_id,
                    'created_by' => auth()->id(), 'type' => 'maintenance', 'category' => 'inventory', 'priority' => 'medium', 'title' => 'Inventory follow-up: '.$inspection->inspection_number, 'status' => 'open', 'progress' => 0,
                    'description' => $issues->map(fn ($r) => $r['room'].' / '.$r['name'].': '.$r['missing'].' missing, '.$r['damaged'].' damaged')->implode("\n")."\nInspection: ".$inspection->id]);
                $task->activities()->create(['user_id' => auth()->id(), 'action' => 'Created', 'comment' => 'Created from approved inventory inspection '.$inspection->inspection_number]);
            }
        });
    }

    // Caller holds an item lock inside a transaction.
    public static function movement(UnitInventoryItem $item, int $quantity, int $damage, string $type, string $reason, ?string $inspectionId = null): void
    {
        $present = $item->present + $quantity;
        $damaged = $item->damaged + $damage;
        if ($present < 0 || $damaged < 0 || $damaged > $present) {
            throw ValidationException::withMessages(['quantity' => 'Movement would create an invalid stock balance.']);
        }
        DB::table('unit_inventory_movements')->insert(['id' => (string) Str::uuid(), 'item_id' => $item->id, 'inspection_id' => $inspectionId, 'user_id' => auth()->id(), 'type' => $type, 'quantity' => $quantity, 'damaged_change' => $damage, 'reason' => $reason, 'created_at' => now(), 'updated_at' => now()]);
        $item->update(['present' => $present, 'damaged' => $damaged, 'version' => $item->version + 1]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['Studio' => 1, '1 BHK' => 1, '2 BHK' => 2] as $name => $bedrooms) {
            $rows = [];
            foreach ([['Living room', 'Television', 1], ['Living room', 'Dining chair', $bedrooms * 2 + 2], ['Kitchen', 'Drinking glass', $bedrooms * 2 + 2], ['Kitchen', 'Dinner plate', $bedrooms * 2 + 2], ['Bathroom', 'Bath towel', $bedrooms * 2 + 2]] as [$room,$item,$qty]) {
                $rows[] = ['room' => $room, 'name' => $item, 'required' => $qty, 'replacement_cost' => 0];
            }
            for ($i = 1; $i <= $bedrooms; $i++) {
                foreach (['Bed' => 1, 'Pillow' => 2, 'Bed sheet' => 2] as $item => $qty) {
                    $rows[] = ['room' => 'Bedroom '.$i, 'name' => $item, 'required' => $qty, 'replacement_cost' => 0];
                }
            }
            if (! DB::table('unit_inventory_templates')->where('name', $name)->exists()) {
                DB::table('unit_inventory_templates')->insert(['id' => (string) Str::uuid(), 'name' => $name, 'rows' => json_encode($rows), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void {} // Preserve templates that users may have edited.
};

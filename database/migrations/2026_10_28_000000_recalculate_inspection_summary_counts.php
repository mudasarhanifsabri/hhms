<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('booking_inspections')->orderBy('id')->chunk(100, function ($inspections) {
            foreach ($inspections as $inspection) {
                $counts = DB::table('booking_inspection_items')
                    ->where('booking_inspection_id', $inspection->id)
                    ->select('condition', DB::raw('COUNT(*) as aggregate'))
                    ->groupBy('condition')
                    ->pluck('aggregate', 'condition');

                DB::table('booking_inspections')->where('id', $inspection->id)->update([
                    'total_items' => (int) $counts->sum(),
                    'good_items' => (int) ($counts['good'] ?? 0),
                    'issue_items' => (int) ($counts['issue'] ?? 0),
                    'na_items' => (int) ($counts['na'] ?? 0),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Corrected summary data should not be reverted.
    }
};

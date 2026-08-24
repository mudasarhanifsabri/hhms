<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_documents')) {
            return;
        }

        DB::table('unit_documents')
            ->where('type', 'management_contract')
            ->select('property_id')
            ->distinct()
            ->orderBy('property_id')
            ->each(function ($row) {
                $contract = DB::table('unit_documents')
                    ->where('property_id', $row->property_id)
                    ->where('type', 'management_contract')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->first();

                if (! $contract) {
                    return;
                }

                foreach (['noc', 'management_letter'] as $type) {
                    $relatedId = DB::table('unit_documents')
                        ->where('property_id', $row->property_id)
                        ->where('type', $type)
                        ->orderByDesc('created_at')
                        ->orderByDesc('id')
                        ->value('id');

                    if ($relatedId) {
                        DB::table('unit_documents')->where('id', $relatedId)->update([
                            'issue_date' => $contract->issue_date,
                            'expires_at' => $contract->expires_at,
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Existing dates cannot be safely reconstructed, so this data sync is intentionally irreversible.
    }
};

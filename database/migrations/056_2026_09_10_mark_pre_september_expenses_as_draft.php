<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('expenses')
            ->whereDate('expense_date', '<', '2026-09-01')
            ->where('approval_status', '!=', 'reversed')
            ->update([
                'approval_status' => 'draft',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Previous workflow statuses cannot be reconstructed safely.
    }
};

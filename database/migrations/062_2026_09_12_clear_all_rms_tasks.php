<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($host !== 'rms.dt-server.com' || ! Schema::hasTable('booking_tasks')) {
            return;
        }

        DB::transaction(function () {
            // Inspections and expenses remain valid business records; only detach
            // their task link before clearing the RMS task workspace.
            if (Schema::hasTable('booking_inspections') && Schema::hasColumn('booking_inspections', 'booking_task_id')) {
                DB::table('booking_inspections')->whereNotNull('booking_task_id')->update(['booking_task_id' => null]);
            }
            if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'booking_task_id')) {
                DB::table('expenses')->whereNotNull('booking_task_id')->update(['booking_task_id' => null]);
            }

            foreach (['booking_task_remarks', 'booking_task_activities', 'booking_task_cost_items'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            DB::table('booking_tasks')->delete();
        });
    }

    public function down(): void
    {
        // RMS task cleanup is intentional and irreversible; HHMS retains its history.
    }
};

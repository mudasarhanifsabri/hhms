<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_inspections', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_inspections', 'booking_task_id')) {
                $table->uuid('booking_task_id')->nullable()->after('property_id')->index();
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE booking_inspections MODIFY booking_id CHAR(36) NULL');
        }
    }

    public function down(): void
    {
        Schema::table('booking_inspections', function (Blueprint $table) {
            if (Schema::hasColumn('booking_inspections', 'booking_task_id')) {
                $table->dropColumn('booking_task_id');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_activity_logs', function (Blueprint $table) {
            $table->string('location_city')->nullable()->after('device');
            $table->string('location_region')->nullable()->after('location_city');
            $table->string('location_country')->nullable()->after('location_region');
            $table->string('location_country_code', 2)->nullable()->after('location_country');
            $table->decimal('location_latitude', 10, 7)->nullable()->after('location_country_code');
            $table->decimal('location_longitude', 10, 7)->nullable()->after('location_latitude');
            $table->string('location_timezone')->nullable()->after('location_longitude');
            $table->timestamp('location_resolved_at')->nullable()->after('location_timezone');
        });
    }

    public function down(): void
    {
        Schema::table('user_activity_logs', function (Blueprint $table) {
            $table->dropColumn(['location_city', 'location_region', 'location_country', 'location_country_code',
                'location_latitude', 'location_longitude', 'location_timezone', 'location_resolved_at']);
        });
    }
};

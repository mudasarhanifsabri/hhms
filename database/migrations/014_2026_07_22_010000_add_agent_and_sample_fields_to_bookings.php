<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->uuid('agent_id')->nullable()->index()->after('property_id');
            $table->time('check_in_time')->nullable()->after('check_in');
            $table->time('check_out_time')->nullable()->after('check_out');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['agent_id', 'check_in_time', 'check_out_time']);
        });
    }
};

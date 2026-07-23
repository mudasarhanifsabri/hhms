<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('management_fee_percent', 5, 2)->default(0)->after('management_fee');
            $table->string('community')->nullable()->after('category');
            $table->string('room_no')->nullable()->after('floor');
            $table->string('unit_floor_label')->nullable()->after('room_no');
            $table->string('parking_number')->nullable()->after('unit_floor_label');
            $table->string('wifi_name')->nullable()->after('wifi_provider');
            $table->string('wifi_password')->nullable()->after('wifi_account_no');
            $table->decimal('utilities_cap', 12, 2)->default(0)->after('wifi_password');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('management_fee_percent', 5, 2)->default(0)->after('rent_amount');
            $table->decimal('management_fee_amount', 12, 2)->default(0)->after('management_fee_percent');
            $table->decimal('owner_rent_income', 12, 2)->default(0)->after('management_fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['management_fee_percent', 'management_fee_amount', 'owner_rent_income']);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'management_fee_percent',
                'community',
                'room_no',
                'unit_floor_label',
                'parking_number',
                'wifi_name',
                'wifi_password',
                'utilities_cap',
            ]);
        });
    }
};

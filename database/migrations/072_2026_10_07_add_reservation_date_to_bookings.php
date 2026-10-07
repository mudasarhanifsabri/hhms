<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'reservation_date')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->date('reservation_date')->nullable()->after('booking_reference');
            });
        }

        DB::table('bookings')->whereNull('reservation_date')->update([
            'reservation_date' => DB::raw('DATE(created_at)'),
        ]);

        DB::table('bookings')->where('booking_reference', 'BK-20260903-QJAHI')->update([
            'reservation_date' => '2026-08-15',
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'reservation_date')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('reservation_date');
            });
        }
    }
};

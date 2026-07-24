<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE properties MODIFY status ENUM('available','booked','under_cleaning','under_maintenance','vacant','rented') NOT NULL DEFAULT 'available'");
        }

        DB::table('properties')->where('status', 'vacant')->update(['status' => 'available']);
        DB::table('properties')->where('status', 'rented')->update(['status' => 'booked']);
    }

    public function down(): void
    {
        DB::table('properties')->where('status', 'available')->update(['status' => 'vacant']);
        DB::table('properties')->whereIn('status', ['booked', 'under_cleaning', 'under_maintenance'])->update(['status' => 'rented']);

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE properties MODIFY status ENUM('rented','vacant') NOT NULL DEFAULT 'vacant'");
        }
    }
};

<?php

use App\Support\AppSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('application_settings')->where('key', 'invoice_legal_name')
            ->where('value', 'Sultan Sameer Saleh Yaslam Alhemeiri')
            ->update(['value' => 'PATTERN Vacation Homes Rental', 'updated_at' => now()]);
        Cache::forget(AppSettings::CACHE_KEY);
    }

    public function down(): void
    {
        // Keep the corrected legal entity name; do not restore the owner's personal name.
    }
};

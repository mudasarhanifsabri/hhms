<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'invoice_establishment_name' => 'PATTERN VACATION HOMES RENTAL',
            'invoice_legal_name' => 'PATTERN Vacation Homes Rental',
            'invoice_trn' => '101001557300003',
            'invoice_address' => 'Ab center building 413, Sheikh zayed road, Al barsha, Dubai, 0000, Dubai',
        ] as $key => $value) {
            if (! DB::table('application_settings')->where('key', $key)->exists()) {
                DB::table('application_settings')->insert(['key' => $key, 'value' => $value, 'group' => 'invoice', 'is_encrypted' => false, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        \Illuminate\Support\Facades\Cache::forget(\App\Support\AppSettings::CACHE_KEY);
    }

    public function down(): void
    {
        // Retain user-managed invoice identity on rollback.
    }
};

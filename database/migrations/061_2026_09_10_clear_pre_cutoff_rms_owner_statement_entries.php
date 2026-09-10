<?php

use App\Models\LandlordAccountEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($host !== 'rms.dt-server.com') {
            return;
        }

        $landlordIds = DB::table('landlord_account_entries')
            ->whereDate('entry_date', '<', '2026-09-01')
            ->pluck('landlord_id')->filter()->unique();

        DB::table('landlord_account_entries')
            ->whereDate('entry_date', '<', '2026-09-01')
            ->delete();

        $landlordIds->each(fn (string $id) => LandlordAccountEntry::recalculateBalancesFor($id));
    }

    public function down(): void
    {
        // RMS remains a forward-only operational ledger; HHMS retains the source history.
    }
};

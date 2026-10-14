<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('landlord_account_entries')
            ->where('type', 'payout')
            ->whereNull('transfer_status')
            ->update(['transfer_status' => 'completed']);

        DB::table('landlord_account_entries')
            ->where('type', 'payout')
            ->whereNull('payment_method')
            ->whereNotNull('reference')
            ->update(['payment_method' => 'bank_transfer']);
    }

    public function down(): void
    {
        // Historical payout information must not be erased during rollback.
    }
};

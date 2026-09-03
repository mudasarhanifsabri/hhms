<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->decimal('agent_commission_percent', 5, 2)->nullable());
        DB::table('bookings')->orderBy('id')->chunkById(200, function ($bookings) {
            foreach ($bookings as $booking) {
                $rate = $booking->agent_id ? (float) DB::table('users')->where('id', $booking->agent_id)->value('agent_commission') : 0;
                DB::table('bookings')->where('id', $booking->id)->update(['agent_commission_percent' => $rate]);
            }
        });
        Schema::table('booking_invoice_payments', function (Blueprint $table) {
            $table->json('allocation')->nullable();
            $table->json('allocation_entry_ids')->nullable();
        });
        foreach ([['2096', 'Guest Receipt Clearing', 'liability'], ['2097', 'Agent Commission Payable', 'liability'], ['2098', 'Tourism Fees Payable', 'liability'], ['4100', 'Management Fee Income', 'income'], ['4110', 'Agency Fee Income', 'income']] as [$code, $name, $type]) {
            if (! DB::table('accounting_accounts')->where('code', $code)->exists()) {
                DB::table('accounting_accounts')->insert(['id' => (string) Str::uuid(), 'code' => $code, 'name' => $name, 'type' => $type, 'parent_code' => $type === 'income' ? '4000' : '2000', 'is_system' => true, 'is_active' => true, 'is_bank_cash' => false, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('agent_commission_percent'));
        Schema::table('booking_invoice_payments', fn (Blueprint $table) => $table->dropColumn(['allocation', 'allocation_entry_ids']));
        // Preserve chart accounts that may already contain financial history.
    }
};

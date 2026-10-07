<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('booking_invoice_payments')
            ->select(['id', 'booking_invoice_id'])
            ->orderBy('id')
            ->chunk(500, function ($payments) {
                foreach ($payments as $payment) {
                    DB::table('landlord_account_entries')
                        ->whereNull('booking_invoice_id')
                        ->whereIn('type', ['rent_income', 'management_fee'])
                        ->where('reference', 'PAY-'.$payment->id)
                        ->update(['booking_invoice_id' => $payment->booking_invoice_id]);
                }
            });
    }

    public function down(): void
    {
        // This is a safe data repair. Existing invoice links must not be removed on rollback.
    }
};

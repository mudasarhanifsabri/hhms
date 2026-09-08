<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landlord_account_entries', function (Blueprint $table) {
            $table->foreignUuid('booking_invoice_id')->nullable()->after('property_id')
                ->constrained('booking_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('landlord_account_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_invoice_id');
        });
    }
};

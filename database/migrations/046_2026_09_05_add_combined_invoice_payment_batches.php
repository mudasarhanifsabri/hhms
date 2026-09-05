<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payment_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id')->index();
            $table->uuid('accounting_entry_id')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->string('reference', 150);
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();
        });
        Schema::table('booking_invoice_payments', function (Blueprint $table) {
            $table->uuid('payment_batch_id')->nullable()->index()->after('booking_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_invoice_payments', fn (Blueprint $table) => $table->dropColumn('payment_batch_id'));
        Schema::dropIfExists('booking_payment_batches');
    }
};

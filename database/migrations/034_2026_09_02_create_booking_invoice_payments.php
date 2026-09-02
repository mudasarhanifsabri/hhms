<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->uuid('renewed_from_booking_id')->nullable()->index()->after('booking_reference');
        });

        Schema::create('booking_invoice_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_invoice_id')->index();
            $table->date('payment_date')->index();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->uuid('bank_account_id')->nullable()->index();
            $table->string('reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('accounting_entry_id')->nullable()->index();
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_invoice_payments');
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('renewed_from_booking_id'));
    }
};

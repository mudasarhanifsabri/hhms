<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('booking_histories');
        Schema::dropIfExists('bookings');

        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('booking_reference')->unique();
            $table->uuid('property_id')->index();
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone');
            $table->string('guest_passport_id_no');
            $table->string('guest_document')->nullable();
            $table->date('check_in');
            $table->date('check_out');
            $table->decimal('rent_amount', 12, 2)->default(0);
            $table->boolean('vat_included')->default(false);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('dtcm_fee', 12, 2)->default(0);
            $table->decimal('cleaning_fee', 12, 2)->default(0);
            $table->decimal('agency_fee', 12, 2)->default(0);
            $table->decimal('security_deposit', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status')->default('confirmed');
            $table->string('invoice_number')->unique();
            $table->string('invoice_status')->default('unpaid');
            $table->string('payment_proof')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

        });

        Schema::create('booking_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_histories');
        Schema::dropIfExists('bookings');
    }
};

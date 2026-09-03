<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('owner_posting_basis')->default('legacy');
        });
        Schema::table('booking_invoice_payments', function (Blueprint $table) {
            $table->decimal('rent_amount', 14, 2)->nullable();
            $table->timestamp('reversed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('booking_invoice_payments', fn (Blueprint $table) => $table->dropColumn(['rent_amount', 'reversed_at']));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('owner_posting_basis'));
    }
};

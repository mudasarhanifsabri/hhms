<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_invoices', fn (Blueprint $table) => $table->boolean('vat_included')->default(false));
    }

    public function down(): void
    {
        Schema::table('booking_invoices', fn (Blueprint $table) => $table->dropColumn('vat_included'));
    }
};

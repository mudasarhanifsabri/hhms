<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_account_type')->nullable()->after('bank_account_number');
        });

        Schema::create('landlord_account_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('landlord_id');
            $table->uuid('property_id')->nullable();
            $table->date('entry_date');
            $table->string('type');
            $table->string('direction');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('landlord_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('set null');
            $table->index(['landlord_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landlord_account_entries');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bank_account_type');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transfer_no')->unique();
            $table->date('transfer_date')->index();
            $table->uuid('from_account_id')->index();
            $table->uuid('to_account_id')->index();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('AED');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();
            $table->foreign('from_account_id')->references('id')->on('bank_accounts');
            $table->foreign('to_account_id')->references('id')->on('bank_accounts');
        });

        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->uuid('bank_transfer_id')->nullable()->index()->after('utility_bill_id');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_entries', fn (Blueprint $table) => $table->dropColumn('bank_transfer_id'));
        Schema::dropIfExists('bank_transfers');
    }
};

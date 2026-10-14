<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('landlord_account_entries', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('reference');
            $table->uuid('bank_account_id')->nullable()->index()->after('payment_method');
            $table->string('transfer_status')->nullable()->index()->after('bank_account_id');
            $table->uuid('created_by')->nullable()->index()->after('transfer_status');
        });
    }

    public function down(): void
    {
        Schema::table('landlord_account_entries', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'bank_account_id', 'transfer_status', 'created_by']);
        });
    }
};

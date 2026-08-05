<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landlord_account_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('landlord_account_entries', 'invoice_attachment')) {
                $table->string('invoice_attachment')->nullable()->after('description');
            }

            if (! Schema::hasColumn('landlord_account_entries', 'receipt_attachment')) {
                $table->string('receipt_attachment')->nullable()->after('invoice_attachment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('landlord_account_entries', function (Blueprint $table) {
            if (Schema::hasColumn('landlord_account_entries', 'receipt_attachment')) {
                $table->dropColumn('receipt_attachment');
            }

            if (Schema::hasColumn('landlord_account_entries', 'invoice_attachment')) {
                $table->dropColumn('invoice_attachment');
            }
        });
    }
};

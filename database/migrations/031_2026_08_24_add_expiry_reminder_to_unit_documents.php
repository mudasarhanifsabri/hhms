<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_documents', function (Blueprint $table) {
            $table->date('expiry_reminder_sent_for')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('unit_documents', fn (Blueprint $table) => $table->dropColumn('expiry_reminder_sent_for'));
    }
};

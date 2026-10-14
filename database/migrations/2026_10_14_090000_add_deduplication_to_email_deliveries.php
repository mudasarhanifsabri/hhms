<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_deliveries', function (Blueprint $table) {
            $table->date('statement_from')->nullable()->after('purpose');
            $table->date('statement_to')->nullable()->after('statement_from');
            $table->string('dedupe_key', 64)->nullable()->unique()->after('statement_to');
        });
    }

    public function down(): void
    {
        Schema::table('email_deliveries', function (Blueprint $table) {
            $table->dropUnique(['dedupe_key']);
            $table->dropColumn(['statement_from', 'statement_to', 'dedupe_key']);
        });
    }
};

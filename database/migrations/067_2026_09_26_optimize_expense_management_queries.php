<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['approval_status', 'expense_date'], 'expenses_status_date_index');
            $table->index(['property_id', 'expense_date'], 'expenses_property_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_status_date_index');
            $table->dropIndex('expenses_property_date_index');
        });
    }
};

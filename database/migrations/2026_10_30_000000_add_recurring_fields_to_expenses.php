<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->boolean('recurring_enabled')->default(false)->index();
            $table->unsignedTinyInteger('recurrence_day')->nullable();
            $table->date('next_recurrence_date')->nullable()->index();
            $table->date('recurring_until')->nullable();
            $table->uuid('recurring_parent_id')->nullable()->index();
            $table->date('recurring_for_month')->nullable();
            $table->unique(['recurring_parent_id', 'recurring_for_month'], 'expenses_recurring_month_unique');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_recurring_month_unique');
            $table->dropColumn(['recurring_enabled','recurrence_day','next_recurrence_date','recurring_until','recurring_parent_id','recurring_for_month']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ceo_review_issues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('week_ending')->index();
            $table->string('issue');
            $table->text('impact')->nullable();
            $table->text('recommendation')->nullable();
            $table->string('owner_name')->nullable();
            $table->date('due_date')->nullable();
            $table->text('decision')->nullable();
            $table->string('status')->default('open')->index();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('decided_by')->nullable()->index();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ceo_review_issues');
    }
};

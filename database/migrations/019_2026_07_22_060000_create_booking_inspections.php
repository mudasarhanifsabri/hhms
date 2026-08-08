<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_inspections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id')->nullable()->index();
            $table->uuid('property_id')->index();
            $table->uuid('booking_task_id')->nullable()->index();
            $table->uuid('submitted_by')->nullable()->index();
            $table->string('inspection_number')->unique();
            $table->string('type');
            $table->string('status')->default('draft');
            $table->json('selected_areas')->nullable();
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('good_items')->default(0);
            $table->unsignedInteger('issue_items')->default(0);
            $table->unsignedInteger('na_items')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_inspection_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_inspection_id')->index();
            $table->string('area');
            $table->string('item');
            $table->string('condition')->default('na');
            $table->text('comment')->nullable();
            $table->json('pictures')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_inspection_items');
        Schema::dropIfExists('booking_inspections');
    }
};

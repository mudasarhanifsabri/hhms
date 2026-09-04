<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Earlier property-only task migration handled MySQL only.
        Schema::table('booking_tasks', fn (Blueprint $t) => $t->uuid('booking_id')->nullable()->change());
        Schema::create('unit_inventory_templates', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name')->unique();
            $t->json('rows');
            $t->timestamps();
        });
        Schema::create('unit_inventory_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('property_id')->constrained('properties');
            $t->string('name');
            $t->string('room');
            $t->unsignedInteger('required')->default(0);
            $t->unsignedInteger('present')->default(0);
            $t->unsignedInteger('damaged')->default(0);
            $t->unsignedInteger('version')->default(0);
            $t->decimal('replacement_cost', 12, 2)->default(0);
            $t->timestamps();
            $t->unique(['property_id', 'room', 'name']);
        });
        Schema::create('unit_inventory_reviews', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('inspection_id')->unique()->constrained('booking_inspections');
            $t->json('rows');
            $t->string('status')->default('draft');
            $t->uuid('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('unit_inventory_movements', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('item_id')->constrained('unit_inventory_items');
            $t->uuid('inspection_id')->nullable();
            $t->uuid('user_id');
            $t->string('type');
            $t->integer('quantity');
            $t->integer('damaged_change')->default(0);
            $t->text('reason');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_inventory_movements');
        Schema::dropIfExists('unit_inventory_reviews');
        Schema::dropIfExists('unit_inventory_items');
        Schema::dropIfExists('unit_inventory_templates');
    }
};

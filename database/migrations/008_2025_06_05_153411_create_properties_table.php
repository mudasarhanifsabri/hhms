<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('landlord_id');
            $table->uuid('building_id')->nullable();
            $table->string('smartlock_id')->nullable();

            $table->string('name');
            $table->string('category')->nullable();
            $table->decimal('rent', 10, 2)->nullable();
            $table->decimal('management_fee', 10, 2)->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedTinyInteger('living_rooms')->nullable();
            $table->unsignedTinyInteger('kitchens')->nullable();
            $table->float('square_foot')->nullable();
            $table->string('floor')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['rented', 'vacant'])->default('vacant');

            $table->json('amenities')->nullable();
            $table->boolean('has_security')->default(false);
            $table->json('security_utilities')->nullable();
            $table->json('additional_features')->nullable();
            $table->string('distance_to_road')->nullable();
            $table->text('additional_notes')->nullable();

            $table->json('photos')->nullable();
            $table->string('video')->nullable();
            $table->string('floor_plan')->nullable();

            $table->string('dtcm_unit_permit')->nullable();
            $table->string('title_deed')->nullable();
            $table->string('dtcm_permit_no')->nullable();
            $table->date('dtcm_permit_expiry')->nullable();

            $table->string('wifi_provider')->nullable();
            $table->string('wifi_account_no')->nullable();
            $table->string('electricity_provider')->nullable();
            $table->string('electricity_account_no')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('landlord_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('building_id')->references('id')->on('buildings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};

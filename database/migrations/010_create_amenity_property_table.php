<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('amenity_property', function (Blueprint $table) {
            $table->uuid('amenity_id');
            $table->uuid('property_id');

            // Set foreign keys
            $table->foreign('amenity_id')
                ->references('id')->on('amenities')
                ->onDelete('cascade');

            $table->foreign('property_id')
                ->references('id')->on('properties')
                ->onDelete('cascade');

            // Composite primary key
            $table->primary(['amenity_id', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenity_property');
    }
};

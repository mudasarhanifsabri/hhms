<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_owner_shares', function (Blueprint $table) {
            $table->id();
            $table->uuid('property_id');
            $table->uuid('owner_id');
            $table->decimal('share_percent', 5, 2)->default(100);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['property_id', 'owner_id']);
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_owner_shares');
    }
};

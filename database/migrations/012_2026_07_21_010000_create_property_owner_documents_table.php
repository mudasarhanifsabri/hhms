<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_owner_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('property_id');
            $table->uuid('landlord_id');
            $table->string('type');
            $table->string('title');
            $table->string('reference_no')->unique();
            $table->string('status')->default('draft');
            $table->string('signing_token')->unique();
            $table->decimal('furniture_amount', 12, 2)->default(0);
            $table->decimal('startup_dtcm_fee', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->longText('unsigned_html')->nullable();
            $table->longText('signed_html')->nullable();
            $table->longText('signature_data')->nullable();
            $table->string('signed_document_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->date('expires_at');
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('landlord_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_owner_documents');
    }
};

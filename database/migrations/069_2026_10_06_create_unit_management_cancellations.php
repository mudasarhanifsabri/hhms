<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_management_cancellations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('property_id')->index();
            $table->uuid('owner_id')->nullable()->index();
            $table->string('reference_no')->unique();
            $table->date('letter_date');
            $table->date('effective_date');
            $table->text('property_description');
            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('company_signer_name');
            $table->string('company_signer_email');
            $table->string('status')->default('sent_to_owner')->index();
            $table->string('owner_token', 80)->unique();
            $table->string('company_token', 80)->unique();
            $table->longText('owner_signature')->nullable();
            $table->string('owner_signed_name')->nullable();
            $table->timestamp('owner_sent_at')->nullable();
            $table->timestamp('owner_viewed_at')->nullable();
            $table->timestamp('owner_signed_at')->nullable();
            $table->longText('company_signature')->nullable();
            $table->string('company_signed_name')->nullable();
            $table->timestamp('company_sent_at')->nullable();
            $table->timestamp('company_viewed_at')->nullable();
            $table->timestamp('company_signed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->date('expires_at');
            $table->string('final_document_path')->nullable();
            $table->string('document_hash', 64)->nullable();
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('unit_management_cancellation_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cancellation_id')->index();
            $table->string('actor_role');
            $table->string('event');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('cancellation_id')->references('id')->on('unit_management_cancellations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_management_cancellation_events');
        Schema::dropIfExists('unit_management_cancellations');
    }
};

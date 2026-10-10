<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->index();
            $table->string('status')->default('queued')->index();
            $table->json('recipients');
            $table->string('subject');
            $table->string('purpose')->nullable();
            $table->uuid('landlord_id')->nullable()->index();
            $table->uuid('property_id')->nullable()->index();
            $table->uuid('created_by')->nullable()->index();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('email_deliveries'); }
};

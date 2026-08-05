<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('profile_photo')->nullable();
                $table->string('name');
                $table->date('dob')->nullable();
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->unique();
                $table->enum('role', ['admin', 'agent', 'landlord', 'tenant', 'maintainer'])->default('tenant');
                $table->string('password');
                $table->string('eid_passport_no')->nullable();
                $table->string('id_document')->nullable();
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_phone')->nullable();
                $table->string('emergency_contact_email')->nullable();
                $table->string('emergency_contact_relationship')->nullable();
                $table->decimal('agent_commission', 8, 2)->default(0.00);
                $table->string('bank_name')->nullable();
                $table->string('bank_account_holder')->nullable();
                $table->string('bank_account_number')->nullable();
                $table->string('swift_code')->nullable();
                $table->string('iban')->nullable();
                $table->string('bank_branch')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->softDeletes();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->uuid('user_id')->nullable()->index();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();
    }
};

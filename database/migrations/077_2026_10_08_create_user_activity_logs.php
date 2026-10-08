<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('user_email')->nullable()->index();
            $table->string('event', 80)->index();
            $table->string('method', 10)->nullable();
            $table->string('route_name')->nullable()->index();
            $table->string('url_path', 500)->nullable();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('forwarded_for', 500)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device', 150)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('user_activity_logs'); }
};

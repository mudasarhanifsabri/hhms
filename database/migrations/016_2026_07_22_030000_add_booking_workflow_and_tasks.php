<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('status');
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_at');
        });

        Schema::create('booking_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id')->index();
            $table->uuid('property_id')->nullable()->index();
            $table->uuid('assigned_to')->nullable()->index();
            $table->string('type')->default('maintenance');
            $table->string('title');
            $table->string('status')->default('open');
            $table->text('description')->nullable();
            $table->json('pictures')->nullable();
            $table->string('invoice_attachment')->nullable();
            $table->string('receipt_attachment')->nullable();
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('material_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_task_remarks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_task_id')->index();
            $table->uuid('user_id')->nullable()->index();
            $table->text('remark');
            $table->json('pictures')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_task_remarks');
        Schema::dropIfExists('booking_tasks');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'checked_out_at']);
        });
    }
};

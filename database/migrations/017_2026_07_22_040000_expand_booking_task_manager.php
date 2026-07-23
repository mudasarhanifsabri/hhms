<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_tasks', function (Blueprint $table) {
            $table->string('task_number')->nullable()->after('id')->index();
            $table->uuid('created_by')->nullable()->after('assigned_to')->index();
            $table->string('category')->default('maintenance')->after('type');
            $table->string('priority')->default('medium')->after('category');
            $table->date('due_date')->nullable()->after('priority');
            $table->unsignedTinyInteger('progress')->default(0)->after('status');
            $table->timestamp('started_at')->nullable()->after('accepted_at');
            $table->date('expected_completion_date')->nullable()->after('started_at');
            $table->text('completion_notes')->nullable()->after('completed_at');
            $table->json('final_images')->nullable()->after('completion_notes');
            $table->decimal('other_expenses', 12, 2)->default(0)->after('material_cost');
            $table->string('warranty_attachment')->nullable()->after('receipt_attachment');
        });

        Schema::create('booking_task_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_task_id')->index();
            $table->uuid('user_id')->nullable()->index();
            $table->string('action');
            $table->text('comment')->nullable();
            $table->decimal('gps_latitude', 10, 7)->nullable();
            $table->decimal('gps_longitude', 10, 7)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_task_cost_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_task_id')->index();
            $table->string('type');
            $table->string('label');
            $table->string('worker')->nullable();
            $table->decimal('hours', 8, 2)->nullable();
            $table->decimal('rate', 12, 2)->nullable();
            $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('booking_task_remarks', function (Blueprint $table) {
            $table->string('status_update')->nullable()->after('remark');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_task_cost_items');
        Schema::dropIfExists('booking_task_activities');

        Schema::table('booking_task_remarks', function (Blueprint $table) {
            $table->dropColumn('status_update');
        });

        Schema::table('booking_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'task_number',
                'created_by',
                'category',
                'priority',
                'due_date',
                'progress',
                'started_at',
                'expected_completion_date',
                'completion_notes',
                'final_images',
                'other_expenses',
                'warranty_attachment',
            ]);
        });
    }
};

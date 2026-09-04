<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $t) {
            $t->uuid('booking_task_id')->nullable()->index();
            $t->string('staff_payment_status')->nullable();
            $t->uuid('staff_submission_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', fn (Blueprint $t) => $t->dropColumn(['booking_task_id', 'staff_payment_status', 'staff_submission_id']));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_management_cancellations', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->uuid('cancelled_by')->nullable()->after('cancelled_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unit_management_cancellations', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_at', 'cancelled_by', 'cancellation_reason']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('owner_approval_status')->nullable()->index()->after('approval_status');
            $table->text('owner_approval_remarks')->nullable()->after('owner_approval_status');
            $table->text('owner_decision_remarks')->nullable()->after('owner_approval_remarks');
            $table->date('owner_approval_deadline')->nullable()->after('owner_decision_remarks');
            $table->timestamp('owner_approval_requested_at')->nullable()->after('owner_approval_deadline');
            $table->timestamp('owner_approval_sent_at')->nullable()->after('owner_approval_requested_at');
            $table->timestamp('owner_decided_at')->nullable()->after('owner_approval_sent_at');
            $table->uuid('owner_decided_by')->nullable()->index()->after('owner_decided_at');
            $table->string('quotation_path')->nullable()->after('invoice_path');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['owner_approval_status', 'owner_approval_remarks', 'owner_decision_remarks', 'owner_approval_deadline', 'owner_approval_requested_at', 'owner_approval_sent_at', 'owner_decided_at', 'owner_decided_by', 'quotation_path']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking_invoices', function (Blueprint $table) {
            $table->string('vat_scope')->default('rent_only')->after('vat_amount');
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->timestamp('reversed_at')->nullable()->after('approval_status');
            $table->uuid('reversed_by')->nullable()->after('reversed_at');
            $table->text('reversal_reason')->nullable()->after('reversed_by');
        });
        Schema::create('expense_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('expense_id')->index();
            $table->uuid('user_id')->nullable()->index();
            $table->string('action');
            $table->text('reason');
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->foreign('expense_id')->references('id')->on('expenses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_audits');
        Schema::table('expenses', fn(Blueprint $table) => $table->dropColumn(['reversed_at','reversed_by','reversal_reason']));
        Schema::table('booking_invoices', fn(Blueprint $table) => $table->dropColumn('vat_scope'));
    }
};

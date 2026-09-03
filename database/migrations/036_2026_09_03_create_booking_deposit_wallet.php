<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_deposit_refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id')->index();
            $table->string('request_no')->unique();
            $table->decimal('held_at_request', 12, 2);
            $table->decimal('deduction_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2);
            $table->json('deductions')->nullable();
            $table->uuid('inspection_id')->nullable();
            $table->text('reason');
            $table->string('status')->default('pending')->index();
            $table->uuid('requested_by');
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
        Schema::create('booking_deposit_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id')->index();
            $table->uuid('booking_invoice_id')->nullable()->index();
            $table->uuid('booking_invoice_payment_id')->nullable()->index();
            $table->uuid('refund_id')->nullable()->index();
            $table->uuid('related_booking_id')->nullable();
            $table->string('kind')->index();
            $table->decimal('amount', 12, 2);
            $table->date('entry_date');
            $table->string('submission_id')->unique();
            $table->uuid('bank_account_id')->nullable();
            $table->uuid('accounting_entry_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('recipient')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
        });
        foreach ([['2030', 'Guest Security Deposits'], ['2095', 'Guest Deposit Deductions Pending Allocation']] as [$code, $name]) {
            if (! DB::table('accounting_accounts')->where('code', $code)->exists()) {
                DB::table('accounting_accounts')->insert(['id' => (string) Str::uuid(), 'code' => $code, 'name' => $name, 'type' => 'liability', 'parent_code' => '2000', 'is_bank_cash' => false, 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_deposit_entries');
        Schema::dropIfExists('booking_deposit_refunds');
    }
};

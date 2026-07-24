<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entry_no')->unique();
            $table->date('entry_date')->index();
            $table->string('type')->index();
            $table->string('category')->nullable()->index();
            $table->text('description')->nullable();
            $table->uuid('property_id')->nullable()->index();
            $table->uuid('landlord_id')->nullable()->index();
            $table->uuid('booking_id')->nullable()->index();
            $table->uuid('expense_id')->nullable()->index();
            $table->uuid('utility_bill_id')->nullable()->index();
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(5);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->string('attachment')->nullable();
            $table->string('status')->default('posted')->index();
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('expense_no')->unique();
            $table->date('expense_date')->index();
            $table->string('category')->index();
            $table->string('supplier')->nullable();
            $table->uuid('property_id')->nullable()->index();
            $table->uuid('landlord_id')->nullable()->index();
            $table->uuid('booking_id')->nullable()->index();
            $table->string('responsibility')->default('company')->index();
            $table->boolean('owner_billable')->default(false);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(5);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('description')->nullable();
            $table->uuid('accounting_entry_id')->nullable()->index();
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('utility_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('property_id')->index();
            $table->string('utility_type')->index();
            $table->string('responsibility')->default('company')->index();
            $table->string('supplier')->nullable();
            $table->string('account_number')->nullable();
            $table->string('username')->nullable();
            $table->text('password_encrypted')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('connection_status')->default('active')->index();
            $table->date('connection_start_date')->nullable();
            $table->date('contract_expiry_date')->nullable()->index();
            $table->unsignedTinyInteger('billing_day')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['property_id', 'utility_type']);
        });

        Schema::create('utility_bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('utility_account_id')->index();
            $table->uuid('property_id')->index();
            $table->uuid('landlord_id')->nullable()->index();
            $table->uuid('booking_id')->nullable()->index();
            $table->date('bill_month')->index();
            $table->date('bill_date')->nullable();
            $table->date('due_date')->nullable()->index();
            $table->decimal('bill_amount', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(5);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('responsibility')->default('company')->index();
            $table->string('status')->default('outstanding')->index();
            $table->timestamp('paid_at')->nullable();
            $table->uuid('paid_by')->nullable()->index();
            $table->string('payment_method')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('expense_id')->nullable()->index();
            $table->uuid('accounting_entry_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('booking_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id')->index();
            $table->string('invoice_number')->unique();
            $table->string('invoice_type')->default('original')->index();
            $table->date('issue_date')->index();
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->decimal('rent_amount', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(5);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->json('fees')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status')->default('unpaid')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_invoices');
        Schema::dropIfExists('utility_bills');
        Schema::dropIfExists('utility_accounts');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('accounting_entries');
    }
};

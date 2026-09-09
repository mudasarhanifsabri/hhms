<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('owner_charge_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number')->unique();
            $table->uuid('landlord_id')->index();
            $table->uuid('property_id')->nullable()->index();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('status')->default('unpaid')->index();
            $table->boolean('cost_finalized')->default(false);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('landlord_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('property_id')->references('id')->on('properties')->nullOnDelete();
        });

        Schema::create('owner_charge_invoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_charge_invoice_id')->index();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 14, 2);
            $table->string('vat_mode')->default('excluded');
            $table->decimal('vat_rate', 5, 2)->default(5);
            $table->decimal('net_amount', 14, 2);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('gross_amount', 14, 2);
            $table->timestamps();
            $table->foreign('owner_charge_invoice_id')->references('id')->on('owner_charge_invoices')->cascadeOnDelete();
        });

        Schema::create('owner_charge_invoice_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_charge_invoice_id')->index();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('method');
            $table->uuid('bank_account_id')->nullable()->index();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('owner_charge_invoice_id')->references('id')->on('owner_charge_invoices')->cascadeOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->uuid('owner_charge_invoice_id')->nullable()->index()->after('booking_id');
            $table->foreign('owner_charge_invoice_id')->references('id')->on('owner_charge_invoices')->nullOnDelete();
        });

        if (Schema::hasTable('accounting_accounts') && ! DB::table('accounting_accounts')->where('code', '4080')->exists()) {
            DB::table('accounting_accounts')->insert([
                'id'=>(string) Str::uuid(),'code'=>'4080','name'=>'Owner Onboarding & Furnishing Income','type'=>'income','parent_code'=>'4000','is_bank_cash'=>false,'is_system'=>true,'is_active'=>true,'description'=>'Revenue from owner furnishing packages, startup services and permits','created_at'=>now(),'updated_at'=>now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('accounting_accounts')->where('code', '4080')->delete();
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['owner_charge_invoice_id']);
            $table->dropColumn('owner_charge_invoice_id');
        });
        Schema::dropIfExists('owner_charge_invoice_payments');
        Schema::dropIfExists('owner_charge_invoice_lines');
        Schema::dropIfExists('owner_charge_invoices');
    }
};

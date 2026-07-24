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
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->index();
            $table->string('parent_code')->nullable()->index();
            $table->boolean('is_bank_cash')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('accounting_account_id')->nullable()->index();
            $table->string('name');
            $table->string('type')->default('bank')->index();
            $table->string('bank_name')->nullable();
            $table->string('iban')->nullable();
            $table->string('account_number')->nullable();
            $table->string('currency')->default('AED');
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('vendor_no')->unique();
            $table->string('name');
            $table->string('category')->nullable()->index();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('trn')->nullable();
            $table->text('address')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->uuid('accounting_account_id')->nullable()->index()->after('category');
            $table->uuid('paid_from_account_id')->nullable()->index()->after('booking_id');
            $table->uuid('vendor_id')->nullable()->index()->after('paid_from_account_id');
            $table->string('approval_status')->default('posted')->index()->after('status');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->uuid('vendor_id')->nullable()->index()->after('category');
            $table->uuid('paid_from_account_id')->nullable()->index()->after('responsibility');
            $table->string('invoice_path')->nullable()->after('receipt_path');
            $table->string('approval_status')->default('pending')->index()->after('invoice_path');
        });

        $this->seedDefaultChartOfAccounts();
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['vendor_id', 'paid_from_account_id', 'invoice_path', 'approval_status']);
        });

        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->dropColumn(['accounting_account_id', 'paid_from_account_id', 'vendor_id', 'approval_status']);
        });

        Schema::dropIfExists('vendors');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('accounting_accounts');
    }

    private function seedDefaultChartOfAccounts(): void
    {
        $accounts = [
            ['1000', 'Assets', 'asset', null, false],
            ['1010', 'Cash in Hand', 'asset', '1000', true],
            ['1020', 'Petty Cash', 'asset', '1000', true],
            ['1030', 'Bank Accounts', 'asset', '1000', true],
            ['1040', 'Security Deposits Receivable', 'asset', '1000', false],
            ['1050', 'Guest Damage Claims', 'asset', '1000', false],
            ['1060', 'Accounts Receivable', 'asset', '1000', false],
            ['1070', 'VAT Receivable', 'asset', '1000', false],
            ['1080', 'Prepaid Expenses', 'asset', '1000', false],
            ['1090', 'Utility Deposits', 'asset', '1000', false],
            ['1500', 'Furniture & Fixtures', 'asset', '1000', false],
            ['1510', 'Office Equipment', 'asset', '1000', false],
            ['1520', 'Vehicles', 'asset', '1000', false],
            ['1530', 'Computers', 'asset', '1000', false],
            ['1590', 'Accumulated Depreciation', 'asset', '1000', false],
            ['2000', 'Liabilities', 'liability', null, false],
            ['2010', 'Accounts Payable', 'liability', '2000', false],
            ['2020', 'Owner Payables', 'liability', '2000', false],
            ['2030', 'Guest Security Deposits', 'liability', '2000', false],
            ['2040', 'VAT Payable', 'liability', '2000', false],
            ['2050', 'Salaries Payable', 'liability', '2000', false],
            ['2060', 'Credit Cards', 'liability', '2000', false],
            ['2070', 'Utility Payables', 'liability', '2000', false],
            ['2080', 'Loans', 'liability', '2000', false],
            ['2090', 'Accrued Expenses', 'liability', '2000', false],
            ['3000', 'Equity', 'equity', null, false],
            ['3010', 'Capital', 'equity', '3000', false],
            ['3020', 'Retained Earnings', 'equity', '3000', false],
            ['3030', 'Current Year Profit', 'equity', '3000', false],
            ['4000', 'Income', 'income', null, false],
            ['4010', 'Rental Income', 'income', '4000', false],
            ['4020', 'Cleaning Charges', 'income', '4000', false],
            ['4030', 'Tourism Fees Collected', 'income', '4000', false],
            ['4040', 'Extra Guest Charges', 'income', '4000', false],
            ['4050', 'Late Checkout', 'income', '4000', false],
            ['4060', 'Early Check-in', 'income', '4000', false],
            ['4070', 'Maintenance Recovery', 'income', '4000', false],
            ['4080', 'Laundry Income', 'income', '4000', false],
            ['4090', 'Utility Recovery', 'income', '4000', false],
            ['4990', 'Miscellaneous Income', 'income', '4000', false],
            ['5000', 'Expenses', 'expense', null, false],
            ['5010', 'DEWA', 'expense', '5000', false],
            ['5020', 'Gas', 'expense', '5000', false],
            ['5030', 'Internet', 'expense', '5000', false],
            ['5040', 'Chiller', 'expense', '5000', false],
            ['5050', 'Cleaning', 'expense', '5000', false],
            ['5060', 'Laundry', 'expense', '5000', false],
            ['5070', 'Maintenance', 'expense', '5000', false],
            ['5080', 'Repairs', 'expense', '5000', false],
            ['5090', 'Staff Salary', 'expense', '5000', false],
            ['5100', 'Visa', 'expense', '5000', false],
            ['5110', 'Office Rent', 'expense', '5000', false],
            ['5120', 'Fuel', 'expense', '5000', false],
            ['5130', 'Vehicle', 'expense', '5000', false],
            ['5140', 'Marketing', 'expense', '5000', false],
            ['5150', 'Booking Commission', 'expense', '5000', false],
            ['5160', 'Airbnb Commission', 'expense', '5000', false],
            ['5170', 'Booking.com Commission', 'expense', '5000', false],
            ['5180', 'Travel Agent Commission', 'expense', '5000', false],
            ['5190', 'Bank Charges', 'expense', '5000', false],
            ['5200', 'Software Subscription', 'expense', '5000', false],
            ['5210', 'Telephone', 'expense', '5000', false],
            ['5220', 'Internet Office', 'expense', '5000', false],
            ['5230', 'Insurance', 'expense', '5000', false],
            ['5240', 'Depreciation', 'expense', '5000', false],
            ['5990', 'Miscellaneous Expenses', 'expense', '5000', false],
        ];

        foreach ($accounts as [$code, $name, $type, $parentCode, $isBankCash]) {
            DB::table('accounting_accounts')->insert([
                'id' => (string) Str::uuid(),
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'parent_code' => $parentCode,
                'is_bank_cash' => $isBankCash,
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};

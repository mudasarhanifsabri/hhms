<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_and_loss_and_bank_balances_are_calculated_from_posted_entries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $incomeAccount = AccountingAccount::where('code', '4010')->firstOrFail();
        $expenseAccount = AccountingAccount::where('code', '5070')->firstOrFail();
        $bank = BankAccount::create([
            'name' => 'Operating Bank',
            'type' => 'bank',
            'opening_balance' => 1000,
            'current_balance' => 1000,
            'currency' => 'AED',
            'is_active' => true,
        ]);

        AccountingEntry::create([
            'entry_no' => 'JE-INCOME-1',
            'entry_date' => now(),
            'type' => 'income',
            'accounting_account_id' => $incomeAccount->id,
            'paid_from_account_id' => $bank->id,
            'credit' => 500,
            'gross_amount' => 500,
            'approval_status' => 'posted',
        ]);
        AccountingEntry::create([
            'entry_no' => 'JE-EXPENSE-1',
            'entry_date' => now(),
            'type' => 'expense',
            'accounting_account_id' => $expenseAccount->id,
            'paid_from_account_id' => $bank->id,
            'debit' => 200,
            'gross_amount' => 200,
            'approval_status' => 'posted',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.accounting.reports', [
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Profit &amp; Loss', false)
            ->assertSee('AED 300.00');

        $this->actingAs($admin)
            ->get(route('admin.accounting.bank-accounts'))
            ->assertOk()
            ->assertSee('AED 1,300.00');
    }
}

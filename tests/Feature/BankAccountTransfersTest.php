<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\BankTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountTransfersTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $name, float $opening): BankAccount
    {
        return BankAccount::create(['name' => $name, 'type' => 'bank', 'currency' => 'AED', 'opening_balance' => $opening, 'current_balance' => $opening, 'is_active' => true]);
    }

    public function test_admin_transfer_posts_balanced_double_entries_and_updates_balances(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $from = $this->account('Operating Bank', 1000);
        $to = $this->account('Petty Cash', 100);

        $this->actingAs($admin)->post(route('admin.accounting.bank-accounts.transfer'), [
            'transfer_date' => '2026-08-25', 'from_account_id' => $from->id,
            'to_account_id' => $to->id, 'amount' => 250, 'reference' => 'BANK-REF-1',
        ])->assertRedirect(route('admin.accounting.bank-account.statement', $from));

        $transfer = BankTransfer::firstOrFail();
        $entries = AccountingEntry::where('bank_transfer_id', $transfer->id)->get();
        $this->assertCount(2, $entries);
        $this->assertSame(250.0, (float) $entries->sum('debit'));
        $this->assertSame(250.0, (float) $entries->sum('credit'));
        $this->assertSame(750.0, (float) $from->fresh()->current_balance);
        $this->assertSame(350.0, (float) $to->fresh()->current_balance);
    }

    public function test_account_edit_and_all_statements_are_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = $this->account('Old Name', 500);

        $this->actingAs($admin)->put(route('admin.accounting.bank-accounts.update', $account), [
            'name' => 'Main Cash', 'type' => 'cash', 'currency' => 'AED',
            'opening_balance' => 600, 'is_active' => 1,
        ])->assertRedirect();
        $this->assertSame('Main Cash', $account->fresh()->name);
        $this->assertSame(600.0, (float) $account->fresh()->current_balance);

        $this->actingAs($admin)->get(route('admin.accounting.bank-statements'))->assertOk()->assertSee('All Account Statements');
        $this->actingAs($admin)->get(route('admin.accounting.bank-account.statement', $account))->assertOk()->assertSee('Opening Balance');
    }

    public function test_transfer_rejects_insufficient_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $from = $this->account('Small Cash', 10);
        $to = $this->account('Main Bank', 100);

        $this->actingAs($admin)->post(route('admin.accounting.bank-accounts.transfer'), [
            'transfer_date' => today()->toDateString(), 'from_account_id' => $from->id,
            'to_account_id' => $to->id, 'amount' => 50,
        ])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('bank_transfers', 0);
        $this->assertDatabaseCount('accounting_entries', 0);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\LandlordAccountEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerStatementEntryDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_entry_and_owner_balances_are_recalculated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $first = LandlordAccountEntry::create(['landlord_id' => $owner->id, 'entry_date' => '2026-08-01', 'type' => 'rent_income', 'direction' => 'credit', 'amount' => 1000, 'balance_after' => 1000]);
        $deleted = LandlordAccountEntry::create(['landlord_id' => $owner->id, 'entry_date' => '2026-08-02', 'type' => 'cleaning', 'direction' => 'debit', 'amount' => 200, 'balance_after' => 800]);
        $last = LandlordAccountEntry::create(['landlord_id' => $owner->id, 'entry_date' => '2026-08-03', 'type' => 'maintenance', 'direction' => 'debit', 'amount' => 100, 'balance_after' => 700]);

        $this->actingAs($admin)->delete(route('admin.accounting.owner-statements.entries.destroy', $deleted))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('landlord_account_entries', ['id' => $deleted->id]);
        $this->assertSame(1000.0, (float) $first->fresh()->balance_after);
        $this->assertSame(900.0, (float) $last->fresh()->balance_after);
    }

    public function test_deleting_expense_statement_entry_prevents_automatic_recreation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $expense = Expense::create(['expense_no' => 'EXP-OWNER-DEL', 'expense_date' => '2026-08-05', 'category' => 'cleaning', 'landlord_id' => $owner->id, 'responsibility' => 'owner', 'owner_billable' => true, 'gross_amount' => 105, 'approval_status' => 'approved']);
        $entry = LandlordAccountEntry::create(['landlord_id' => $owner->id, 'entry_date' => '2026-08-05', 'type' => 'cleaning', 'direction' => 'debit', 'amount' => 105, 'reference' => $expense->expense_no]);

        $this->actingAs($admin)->delete(route('admin.accounting.owner-statements.entries.destroy', $entry))->assertRedirect();

        $this->assertFalse($expense->fresh()->owner_billable);
    }

    public function test_owner_cannot_delete_statement_entry(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $entry = LandlordAccountEntry::create(['landlord_id' => $owner->id, 'entry_date' => '2026-08-01', 'type' => 'rent_income', 'direction' => 'credit', 'amount' => 100]);

        $this->actingAs($owner)->delete(route('admin.accounting.owner-statements.entries.destroy', $entry))->assertForbidden();
        $this->assertDatabaseHas('landlord_account_entries', ['id' => $entry->id]);
    }

    public function test_delete_option_is_visible_in_owner_account_statement_tab(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $entry = LandlordAccountEntry::create(['landlord_id' => $owner->id, 'entry_date' => '2026-08-01', 'type' => 'adjustment_credit', 'direction' => 'credit', 'amount' => 100]);

        $this->actingAs($admin)->get(route('admin.landlord.account-statement', $owner->id))
            ->assertOk()
            ->assertSee(url('/admin/accounting/owner-statements/entries/' . $entry->id), false)
            ->assertSee('Delete statement entry');
    }

    public function test_admin_can_add_dynamic_statement_category_and_owner_loan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);

        $this->actingAs($admin)->post(route('admin.landlord.account-entry.store', $owner->id), [
            'entry_date' => '2026-08-26', 'type' => '__custom__', 'custom_type' => 'Owner Renovation Advance',
            'custom_direction' => 'debit', 'amount' => 25000, 'description' => 'Recover from future rental income',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('landlord_account_entries', ['landlord_id' => $owner->id, 'type' => 'owner_renovation_advance', 'direction' => 'debit', 'amount' => 25000]);
        $this->actingAs($admin)->get(route('admin.landlord.account-statement', $owner->id))
            ->assertOk()->assertSee('Owner Renovation Advance')->assertSee('Owner Loan / Advance');
    }
}

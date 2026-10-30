<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_owner_expense_is_created_once_as_pending_for_approval(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => '633']);
        $template = Expense::create([
            'expense_no' => 'EXP-RECURRING-BASE', 'expense_date' => '2026-10-15', 'category' => 'internet',
            'property_id' => $unit->id, 'landlord_id' => $owner->id, 'responsibility' => 'owner',
            'owner_billable' => true, 'net_amount' => 210, 'vat_rate' => 0, 'vat_amount' => 0,
            'gross_amount' => 210, 'approval_status' => 'approved', 'description' => 'Monthly internet',
            'recurring_enabled' => true, 'recurrence_day' => 15, 'next_recurrence_date' => '2026-11-15',
        ]);
        $template->update(['recurring_parent_id' => $template->id]);

        $this->artisan('expenses:generate-recurring', ['--date' => '2026-11-15'])->assertSuccessful();
        $this->artisan('expenses:generate-recurring', ['--date' => '2026-11-15'])->assertSuccessful();

        $copy = Expense::where('recurring_parent_id', $template->id)->whereNot('id', $template->id)->sole();
        $this->assertSame('pending', $copy->approval_status);
        $this->assertTrue($copy->owner_billable);
        $this->assertSame('internet', $copy->category);
        $this->assertEquals(210, $copy->gross_amount);
        $this->assertSame('2026-11-15', $copy->expense_date->toDateString());
        $this->assertSame('2026-12-15', $template->fresh()->next_recurrence_date->toDateString());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Expense;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitExpenseNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_can_open_unit_expenses_and_unit_is_fixed_in_new_expense_form(): void
    {
        $accountant = User::factory()->create(['role' => 'admin']);
        $accountant->syncRoles(['Accounting']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $building = Building::create(['building_name' => 'Riviera 44', 'address' => 'Al Merkadh, Dubai']);
        $unit = Property::create(['landlord_id' => $owner->id, 'building_id' => $building->id, 'name' => '501']);
        Expense::create(['expense_no'=>'EXP-UNIT-501','expense_date'=>'2026-10-15','category'=>'internet','property_id'=>$unit->id,
            'landlord_id'=>$owner->id,'responsibility'=>'company','owner_billable'=>false,'net_amount'=>210,'vat_rate'=>0,
            'vat_amount'=>0,'gross_amount'=>210,'approval_status'=>'pending','description'=>'Unit internet']);

        $this->actingAs($accountant)->get(route('admin.property.expenses', $unit))
            ->assertOk()->assertSee('Unit Expenses · 501')->assertSee('Add New Expense')
            ->assertSee('EXP-UNIT-501')->assertSee('name="property_id" value="'.$unit->id.'"', false)
            ->assertDontSee('General Company Expense');
    }
}

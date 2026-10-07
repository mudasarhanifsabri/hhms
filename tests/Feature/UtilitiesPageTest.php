<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use App\Models\UtilityAccount;
use App\Models\UtilityBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UtilitiesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_utilities_page_has_clear_monthly_follow_up_and_filters(): void
    {
        $this->travelTo(Carbon::parse('2026-10-07 10:00:00'));
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $unitA = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit A', 'status' => 'rented']);
        $unitB = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit B', 'status' => 'vacant']);
        $dewa = UtilityAccount::create(['property_id' => $unitA->id, 'utility_type' => 'dewa', 'responsibility' => 'company', 'supplier' => 'DEWA', 'connection_status' => 'active']);
        UtilityAccount::create(['property_id' => $unitB->id, 'utility_type' => 'internet', 'responsibility' => 'owner', 'supplier' => 'Du', 'connection_status' => 'active']);
        UtilityBill::create(['utility_account_id' => $dewa->id, 'property_id' => $unitA->id, 'landlord_id' => $owner->id, 'bill_month' => '2026-10-01', 'bill_amount' => 100, 'vat_rate' => 5, 'vat_amount' => 5, 'total_amount' => 105, 'responsibility' => 'company', 'status' => 'outstanding', 'due_date' => '2026-10-05']);

        $this->actingAs($admin)->get(route('admin.accounting.utilities', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('Simple workflow')
            ->assertSee('Bills Not Recorded')
            ->assertSee('AED 105.00')
            ->assertSee('Overdue')
            ->assertSee('Record Bill');

        $this->actingAs($admin)->get(route('admin.accounting.utilities', ['month' => '2026-10', 'property_id' => $unitB->id, 'bill_status' => 'missing']))
            ->assertOk()
            ->assertSee('Unit B')
            ->assertSee('Internet')
            ->assertDontSee('Unit A</strong>', false);
    }
}

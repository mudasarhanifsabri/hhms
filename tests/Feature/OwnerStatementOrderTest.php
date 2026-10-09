<?php

namespace Tests\Feature;

use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerStatementOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_rent_precedes_its_fee_and_balances_follow_display_order(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $base = ['landlord_id' => $owner->id, 'entry_date' => '2026-09-01', 'reference' => 'INV-1'];
        $fee = LandlordAccountEntry::create($base + ['type' => 'management_fee', 'direction' => 'debit', 'amount' => 100]);
        $rent = LandlordAccountEntry::create($base + ['type' => 'rent_income', 'direction' => 'credit', 'amount' => 1000]);
        $this->assertSame([$rent->id, $fee->id], LandlordAccountEntry::statementOrder()->pluck('id')->all());
        $balances = LandlordAccountEntry::statementBalancesFor($owner->id);
        $this->assertEquals(1000, $balances[$rent->id]);
        $this->assertEquals(900, $balances[$fee->id]);
        LandlordAccountEntry::recalculateBalancesFor($owner->id);
        $this->assertEquals(1000, $rent->fresh()->balance_after);
        $this->assertEquals(900, $fee->fresh()->balance_after);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.landlord.account-statement', $owner->id))
            ->assertOk()->assertViewHas('accountEntries', fn ($entries) => $entries->pluck('id')->all() === [$rent->id, $fee->id]);
    }

    public function test_filtered_unit_statement_balances_exclude_other_unit_transactions(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => '1205']);
        $otherUnit = Property::create(['landlord_id' => $owner->id, 'name' => 'Other Unit']);
        $internet = LandlordAccountEntry::create([
            'landlord_id' => $owner->id, 'property_id' => $unit->id, 'entry_date' => '2026-09-15',
            'type' => 'internet', 'direction' => 'debit', 'amount' => 399,
        ]);
        LandlordAccountEntry::create([
            'landlord_id' => $owner->id, 'property_id' => $otherUnit->id, 'entry_date' => '2026-09-20',
            'type' => 'rent_income', 'direction' => 'credit', 'amount' => 6200,
        ]);
        $maintenance = LandlordAccountEntry::create([
            'landlord_id' => $owner->id, 'property_id' => $unit->id, 'entry_date' => '2026-10-02',
            'type' => 'maintenance', 'direction' => 'debit', 'amount' => 812.50,
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.landlord.account-statement', [
                $owner->id, 'year' => '2026', 'property_id' => $unit->id,
            ]))->assertOk()->assertSee('Period net AED -1,211.50');

        $entries = $response->viewData('accountEntries');
        $this->assertEquals(-399, (float) $entries->firstWhere('id', $internet->id)->balance_after);
        $this->assertEquals(-1211.50, (float) $entries->firstWhere('id', $maintenance->id)->balance_after);
    }
}

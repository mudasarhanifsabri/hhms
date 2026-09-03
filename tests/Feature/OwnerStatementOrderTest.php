<?php

namespace Tests\Feature;

use App\Models\LandlordAccountEntry;
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
}

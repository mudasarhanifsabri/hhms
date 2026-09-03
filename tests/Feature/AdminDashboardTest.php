<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\UnitDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_live_bookings_and_latest_wallet_permits(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 3)->startOfDay());
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $occupied = Property::create(['landlord_id' => $owner->id, 'name' => 'Occupied', 'status' => 'vacant']);
        $future = Property::create(['landlord_id' => $owner->id, 'name' => 'Future', 'status' => 'rented', 'dtcm_permit_expiry' => '2026-09-05']);
        $deleted = Property::create(['landlord_id' => $owner->id, 'name' => 'Deleted']);
        foreach ([[$occupied, 'checked_in', '2026-09-03', '2026-09-03'], [$occupied, 'checked_in', '2026-09-01', '2026-09-02'], [$future, 'confirmed', '2026-10-01', '2026-10-05'], [$future, 'confirmed', '2026-09-03', '2026-09-05'], [$future, 'cancelled', '2026-09-03', '2026-09-05'], [$deleted, 'checked_in', '2026-09-01', '2026-09-03']] as [$unit, $status, $in, $out]) {
            Booking::create(['property_id' => $unit->id, 'booking_reference' => uniqid('BK-'), 'invoice_number' => uniqid('INV-'), 'guest_name' => 'Guest', 'guest_email' => 'guest@example.com', 'guest_phone' => '12345', 'guest_passport_id_no' => 'P100', 'check_in' => $in, 'check_out' => $out, 'status' => $status, 'rent_amount' => 100]);
        }
        UnitDocument::create(['property_id' => $occupied->id, 'type' => 'dtcm_permit', 'expires_at' => '2026-09-10', 'file_path' => 'permit.pdf']);
        $old = UnitDocument::create(['property_id' => $future->id, 'type' => 'dtcm_permit', 'expires_at' => '2026-09-05', 'file_path' => 'old.pdf']);
        $old->forceFill(['created_at' => now()->subDay()])->save();
        UnitDocument::create(['property_id' => $future->id, 'type' => 'dtcm_permit', 'expires_at' => '2027-09-05', 'file_path' => 'new.pdf']);
        $deleted->delete();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('totalProperties', 2)->assertViewHas('occupiedUnits', 1)
            ->assertViewHas('occupancyPercent', 50)->assertViewHas('upcomingDtcmExpiry', 1)
            ->assertViewHas('arrivalsToday', 1)->assertViewHas('departuresToday', 1)
            ->assertViewHas('overdueDepartures', 1)->assertViewHas('totalRegisteredUsers', 2)
            ->assertSee('05 Sep 2027')->assertDontSee('05 Sep 2026');
    }

    public function test_empty_dashboard_has_zero_occupancy(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('totalProperties', 0)->assertViewHas('occupancyPercent', 0)
            ->assertViewHas('upcomingDtcmExpiry', 0);
    }
}

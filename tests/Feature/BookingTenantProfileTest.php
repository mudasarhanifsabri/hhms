<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use App\Support\BookingTenantProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTenantProfileTest extends TestCase
{
    use RefreshDatabase;

    private function booking(string $email = 'guest@example.com'): Booking
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit G1']);

        return Booking::create(['property_id' => $unit->id, 'booking_reference' => 'BK-'.uniqid(), 'invoice_number' => 'INV-'.uniqid(), 'guest_name' => 'Guest One', 'guest_email' => $email,
            'guest_phone' => '12345', 'guest_passport_id_no' => 'P100', 'check_in' => '2026-10-01', 'check_out' => '2026-10-05', 'rent_amount' => 1000, 'status' => 'confirmed']);
    }

    public function test_guest_is_copied_without_duplicate_accounts_and_completes_profile_at_login(): void
    {
        $booking = $this->booking();
        $tenant = BookingTenantProfile::sync($booking);
        $this->assertSame('tenant', $tenant->role);
        $this->assertSame('P100', $tenant->eid_passport_no);
        $this->assertSame($tenant->id, $booking->fresh()->tenant_id);
        $this->assertTrue($tenant->tenant_profile_required);
        $this->assertSame($tenant->id, BookingTenantProfile::sync($booking)->id);
        $other = $this->booking();
        $this->assertSame($tenant->id, BookingTenantProfile::sync($other)->id);
        $this->assertSame(1, User::where('role', 'tenant')->count());
        $this->actingAs($tenant)->get(route('tenant.dashboard'))->assertRedirect(route('tenant.profile.edit'));
        $this->get(route('tenant.booking.show', $booking))->assertRedirect(route('tenant.profile.edit'));
        $this->get(route('tenant.profile.edit'))->assertOk()->assertSee('Guest One');
        $this->put(route('tenant.profile.update'), [])->assertSessionHasErrors('nationality');
        $this->put(route('tenant.profile.update'), ['name' => 'Guest One', 'phone' => '12345', 'eid_passport_no' => 'P100',
            'nationality' => 'Example nationality', 'dob' => '1990-01-01', 'address' => 'Home address', 'role' => 'admin', 'email' => 'attacker@example.com'])
            ->assertSessionHasNoErrors()->assertRedirect(route('tenant.dashboard'));
        $this->assertFalse($tenant->fresh()->tenant_profile_required);
        $this->assertSame('tenant', $tenant->fresh()->role);
        $this->assertSame('guest@example.com', $tenant->fresh()->email);
        $this->get(route('tenant.dashboard'))->assertOk()->assertSee($booking->booking_reference);
    }

    public function test_admin_tenant_pages_show_linked_bookings_without_unrelated_units(): void
    {
        $booking = $this->booking();
        $tenant = BookingTenantProfile::sync($booking);
        $unrelatedOwner = User::factory()->create(['role' => 'landlord']);
        Property::create(['landlord_id' => $unrelatedOwner->id, 'name' => 'UNRELATED-UNIT']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.tenant.index'))->assertOk()->assertSee('Profile pending')
            ->assertViewHas('tenants', fn ($rows) => (int) $rows->first()->tenant_bookings_count === 1);
        $this->get(route('admin.tenant.grid'))->assertOk()->assertSee($tenant->name);
        $this->get(route('admin.tenant.show', $tenant->id))->assertOk()
            ->assertViewHas('relatedProperties', fn ($rows) => $rows->pluck('id')->all() === [$booking->property_id]);
    }

    public function test_linked_tenant_stays_synchronized_when_booking_guest_details_change(): void
    {
        $booking = $this->booking();
        $tenant = BookingTenantProfile::sync($booking);
        $tenant->update(['nationality' => 'Emirati', 'dob' => '1990-01-01', 'address' => 'Dubai']);

        $booking->update([
            'guest_name' => 'Updated Guest',
            'guest_email' => 'updated@example.com',
            'guest_phone' => '0501234567',
            'guest_passport_id_no' => 'P200',
        ]);

        BookingTenantProfile::sync($booking->fresh());
        $tenant->refresh();

        $this->assertSame('Updated Guest', $tenant->name);
        $this->assertSame('updated@example.com', $tenant->email);
        $this->assertSame('0501234567', $tenant->phone);
        $this->assertSame('P200', $tenant->eid_passport_no);
        $this->assertSame('Emirati', $tenant->nationality);
        $this->assertSame('Dubai', $tenant->address);
    }

    public function test_conflicts_never_link_another_role_or_identity(): void
    {
        $booking = $this->booking('owner@example.com');
        User::factory()->create(['email' => 'owner@example.com', 'role' => 'landlord']);
        $this->assertNull(BookingTenantProfile::sync($booking));
        $this->assertNull($booking->fresh()->tenant_id);
        $booking2 = $this->booking();
        $tenant = User::factory()->create(['email' => 'guest@example.com', 'role' => 'tenant', 'eid_passport_no' => 'DIFFERENT']);
        $this->assertNull(BookingTenantProfile::sync($booking2));
        $this->actingAs($tenant)->get(route('tenant.booking.show', $booking2))->assertForbidden();
        $this->assertSame('DIFFERENT', $tenant->fresh()->eid_passport_no);
    }
}

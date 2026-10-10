<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
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

        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('totalProperties', 2)->assertViewHas('occupiedUnits', 2)
            ->assertViewHas('emptyUnits', 0)->assertViewHas('occupancyPercent', 100)->assertViewHas('upcomingDtcmExpiry', 1)
            ->assertViewHas('arrivalsToday', 1)->assertViewHas('departuresToday', 1)
            ->assertViewHas('overdueDepartures', 1)->assertViewHas('totalRegisteredUsers', 2)
            ->assertSee('05 Sep 2027')
            ->assertSee('Checkout 1 day overdue')
            ->assertSee('Occupied 2')->assertSee('Empty 0');
        $this->assertCount(3, $response->viewData('expiringBookings'));
    }

    public function test_empty_dashboard_has_zero_occupancy(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('totalProperties', 0)->assertViewHas('occupancyPercent', 0)
            ->assertViewHas('emptyUnits', 0)
            ->assertViewHas('upcomingDtcmExpiry', 0);
    }

    public function test_unit_list_uses_live_occupied_and_vacant_statuses(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 10)->startOfDay());
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $occupied = Property::create(['landlord_id' => $owner->id, 'name' => 'Live Occupied', 'status' => 'vacant']);
        $vacant = Property::create(['landlord_id' => $owner->id, 'name' => 'Live Vacant', 'status' => 'rented']);

        Booking::create(['property_id' => $occupied->id, 'booking_reference' => 'BK-LIVE', 'invoice_number' => 'INV-LIVE', 'guest_name' => 'Live Guest', 'guest_email' => 'live@example.com', 'guest_phone' => '1', 'guest_passport_id_no' => 'P1', 'check_in' => '2026-10-01', 'check_out' => '2026-10-20', 'status' => 'confirmed', 'rent_amount' => 100]);
        Booking::create(['property_id' => $vacant->id, 'booking_reference' => 'BK-FUTURE', 'invoice_number' => 'INV-FUTURE', 'guest_name' => 'Future Guest', 'guest_email' => 'future@example.com', 'guest_phone' => '2', 'guest_passport_id_no' => 'P2', 'check_in' => '2026-11-01', 'check_out' => '2026-11-20', 'status' => 'confirmed', 'rent_amount' => 100]);

        $response = $this->actingAs($admin)->get(route('admin.property.index'))->assertOk()
            ->assertSee('Occupied')->assertSee('Vacant')->assertDontSee('Booked</span>', false);
        $this->assertSame(['total' => 2, 'vacant' => 1, 'occupied' => 1, 'attention' => 0], $response->viewData('unitStats'));
        $rows = $response->viewData('properties')->getCollection()->keyBy('id');
        $this->assertSame('Occupied', $rows[$occupied->id]->occupancy_label);
        $this->assertSame('Vacant', $rows[$vacant->id]->occupancy_label);
    }

    public function test_pending_invoices_appear_three_days_before_due_and_remain_until_paid(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 6)->startOfDay());
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit 501', 'status' => 'rented']);
        $booking = Booking::create(['property_id' => $property->id, 'booking_reference' => 'BK-PENDING', 'invoice_number' => 'INV-PENDING', 'guest_name' => 'Pending Guest', 'guest_email' => 'guest@example.com', 'guest_phone' => '12345', 'guest_passport_id_no' => 'P100', 'check_in' => '2026-10-01', 'check_out' => '2026-12-01', 'status' => 'checked_in', 'rent_amount' => 100]);

        $overdue = BookingInvoice::create(['booking_id' => $booking->id, 'invoice_number' => 'INV-OVERDUE', 'invoice_type' => 'original', 'issue_date' => '2026-09-01', 'period_from' => '2026-09-01', 'period_to' => '2026-09-30', 'due_date' => '2026-10-01', 'total_amount' => 1000, 'status' => 'unpaid']);
        $soon = BookingInvoice::create(['booking_id' => $booking->id, 'invoice_number' => 'INV-SOON', 'invoice_type' => 'extension', 'issue_date' => '2026-10-01', 'period_from' => '2026-10-09', 'period_to' => '2026-11-08', 'due_date' => null, 'total_amount' => 900, 'status' => 'unpaid']);
        BookingInvoice::create(['booking_id' => $booking->id, 'invoice_number' => 'INV-FUTURE', 'invoice_type' => 'extension', 'issue_date' => '2026-10-01', 'period_from' => '2026-10-10', 'period_to' => '2026-11-09', 'due_date' => '2026-10-10', 'total_amount' => 800, 'status' => 'unpaid']);
        BookingInvoicePayment::create(['booking_invoice_id' => $overdue->id, 'amount' => 1000, 'payment_date' => '2026-10-06', 'payment_method' => 'cash']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Pending Booking Invoices')
            ->assertSee('INV-SOON')
            ->assertDontSee('INV-FUTURE')
            ->assertDontSee('INV-OVERDUE');

        $this->assertCount(1, $response->viewData('pendingInvoices'));
        $this->assertSame($soon->id, $response->viewData('pendingInvoices')->first()->id);
    }

    public function test_paid_extension_replaces_old_checkout_date_in_follow_up(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 10)->startOfDay());
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit 719']);
        $booking = Booking::create([
            'property_id' => $property->id, 'booking_reference' => 'BK-PAID-EXTENSION',
            'invoice_number' => 'INV-ORIGINAL', 'guest_name' => 'Extended Guest',
            'guest_email' => 'extended@example.com', 'guest_phone' => '12345',
            'guest_passport_id_no' => 'P-EXT', 'check_in' => '2026-09-01',
            'check_out' => '2026-10-02', 'status' => 'checked_in', 'rent_amount' => 100,
        ]);
        $extension = BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-EXT-PAID',
            'invoice_type' => 'extension', 'issue_date' => '2026-10-01',
            'period_from' => '2026-10-03', 'period_to' => '2026-11-02',
            'total_amount' => 1000, 'status' => 'paid',
        ]);
        BookingInvoicePayment::create([
            'booking_invoice_id' => $extension->id, 'amount' => 1000,
            'payment_date' => '2026-10-01', 'payment_method' => 'Bank Transfer',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $this->assertFalse($response->viewData('expiringBookings')->contains('id', $booking->id));
        $response->assertDontSee('BK-PAID-EXTENSION');
    }
}

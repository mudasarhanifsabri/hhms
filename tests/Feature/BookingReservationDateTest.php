<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingReservationDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_date_is_separate_from_check_in_and_printed_on_confirmation(): void
    {
        User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Reservation Date Unit', 'status' => 'rented']);
        $booking = Booking::create([
            'property_id' => $property->id, 'booking_reference' => 'BK-20260903-QJAHI', 'invoice_number' => 'INV-RES-DATE',
            'reservation_date' => '2026-08-15', 'guest_name' => 'Reservation Guest', 'guest_email' => 'guest@example.com',
            'guest_phone' => '123', 'guest_passport_id_no' => 'P123', 'check_in' => '2026-08-27', 'check_out' => '2026-09-27',
            'rent_amount' => 5000, 'total_amount' => 5000, 'status' => 'confirmed', 'invoice_status' => 'paid',
        ]);

        $this->assertSame('2026-08-15', $booking->reservation_date->toDateString());
        $this->assertSame('2026-08-27', $booking->check_in->toDateString());

        $html = view('admin.bookings.pdf.confirmation', ['booking' => $booking->load(['property.building', 'agent'])])->render();
        $this->assertStringContainsString('Reservation date', $html);
        $this->assertStringContainsString('15 Aug 2026', $html);
        $this->assertStringContainsString('27 Aug 2026', $html);
    }

    public function test_data_repair_sets_requested_date_without_changing_check_in(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Migration Unit']);
        $booking = Booking::create([
            'property_id' => $property->id, 'booking_reference' => 'BK-20260903-QJAHI', 'invoice_number' => 'INV-MIGRATION',
            'guest_name' => 'Migration Guest', 'guest_email' => 'migration@example.com', 'guest_phone' => '123',
            'guest_passport_id_no' => 'P456', 'check_in' => '2026-08-27', 'check_out' => '2026-09-27',
            'rent_amount' => 5000, 'total_amount' => 5000, 'status' => 'confirmed', 'invoice_status' => 'unpaid',
        ]);

        $migration = require database_path('migrations/072_2026_10_07_add_reservation_date_to_bookings.php');
        $migration->up();

        $booking->refresh();
        $this->assertSame('2026-08-15', $booking->reservation_date->toDateString());
        $this->assertSame('2026-08-27', $booking->check_in->toDateString());
    }

    public function test_reservation_form_is_available_without_paid_invoices(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Reservation Form Unit']);
        $booking = Booking::create([
            'property_id' => $property->id, 'booking_reference' => 'BK-RES-FORM', 'invoice_number' => 'INV-RES-FORM',
            'reservation_date' => '2026-08-15', 'guest_name' => 'Form Guest', 'guest_email' => 'form@example.com',
            'guest_phone' => '123', 'guest_passport_id_no' => 'P789', 'check_in' => '2026-08-27', 'check_out' => '2026-09-27',
            'rent_amount' => 5000, 'total_amount' => 5000, 'status' => 'confirmed', 'invoice_status' => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.booking.reservation-form', $booking));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}

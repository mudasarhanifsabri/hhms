<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\Property;
use App\Models\User;
use App\Support\BookingPaymentSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingListPaymentSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_checked_out_bookings_are_kept_in_the_separate_archive(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => 'Archive 101', 'status' => 'vacant']);

        foreach ([['BK-ACTIVE-101', 'checked_in'], ['BK-ARCHIVE-101', 'checked_out']] as [$reference, $status]) {
            Booking::create([
                'property_id' => $unit->id,
                'booking_reference' => $reference,
                'invoice_number' => 'INV-'.$reference,
                'guest_name' => 'Archive Test Guest',
                'guest_email' => strtolower($reference).'@example.com',
                'guest_phone' => '0500000000',
                'guest_passport_id_no' => 'P-'.$reference,
                'check_in' => '2026-09-01',
                'check_out' => '2026-10-01',
                'status' => $status,
                'invoice_status' => 'paid',
            ]);
        }

        $this->actingAs($admin)->get(route('admin.booking.index'))
            ->assertOk()->assertSee('BK-ACTIVE-101')->assertDontSee('BK-ARCHIVE-101')
            ->assertSee('Checked-Out Archive');

        $this->actingAs($admin)->get(route('admin.booking.checked-out'))
            ->assertOk()->assertSee('Checked-Out Booking Archive')
            ->assertSee('BK-ARCHIVE-101')->assertDontSee('BK-ACTIVE-101');
    }

    public function test_list_shows_paid_first_invoice_and_partial_second_invoice_with_breakdown(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => '502', 'status' => 'rented']);
        $booking = Booking::create(['property_id' => $unit->id, 'booking_reference' => 'BK-LIST-PAY', 'invoice_number' => 'INV-FIRST',
            'guest_name' => 'Payment Guest', 'guest_email' => 'guest@example.com', 'guest_phone' => '0500000000',
            'guest_passport_id_no' => 'P123', 'check_in' => '2026-09-01', 'check_out' => '2026-11-01',
            'total_amount' => 2000, 'status' => 'confirmed', 'invoice_status' => 'unpaid']);
        $first = BookingInvoice::create(['booking_id' => $booking->id, 'invoice_number' => 'INV-FIRST',
            'invoice_type' => 'original', 'issue_date' => '2026-09-01', 'period_from' => '2026-09-01',
            'total_amount' => 1000, 'status' => 'paid']);
        $second = BookingInvoice::create(['booking_id' => $booking->id, 'invoice_number' => 'INV-SECOND',
            'invoice_type' => 'extension', 'issue_date' => '2026-10-01', 'period_from' => '2026-10-01',
            'total_amount' => 1000, 'status' => 'partial']);
        $first->payments()->create(['payment_date' => '2026-09-01', 'amount' => 1000]);
        $second->payments()->create(['payment_date' => '2026-10-01', 'amount' => 400]);

        $response = $this->actingAs($admin)->get(route('admin.booking.index'));
        $response->assertOk()->assertSee('Partly paid')->assertSee('1 paid · 1 partial · 0 unpaid')
            ->assertSee('INV-FIRST')->assertSee('INV-SECOND')->assertSee('AED 1,400.00')
            ->assertSee('AED 600.00')->assertSee('View invoices');

        (require database_path('migrations/062_2026_09_19_sync_booking_payment_statuses.php'))->up();
        $this->assertSame('partial', $booking->fresh()->invoice_status);
        $this->actingAs($admin)->get(route('admin.booking.index', ['invoice_status' => 'partial']))
            ->assertOk()->assertSee('BK-LIST-PAY');
    }

    public function test_reversed_payment_does_not_make_stale_paid_invoice_look_paid(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => '503', 'status' => 'rented']);
        $booking = Booking::create(['property_id' => $unit->id, 'booking_reference' => 'BK-REVERSED', 'invoice_number' => 'INV-REVERSED',
            'guest_name' => 'Guest', 'guest_email' => 'reversed@example.com', 'guest_phone' => '0500000001',
            'guest_passport_id_no' => 'P124', 'check_in' => '2026-09-01', 'check_out' => '2026-10-01',
            'status' => 'confirmed', 'invoice_status' => 'paid']);
        $invoice = BookingInvoice::create(['booking_id' => $booking->id, 'invoice_number' => 'INV-REVERSED',
            'invoice_type' => 'original', 'issue_date' => '2026-09-01', 'total_amount' => 100,
            'status' => 'paid']);
        $invoice->payments()->create(['payment_date' => '2026-09-01', 'amount' => 100, 'reversed_at' => now()]);

        $this->assertSame('unpaid', BookingPaymentSummary::for($booking)['status']);
        BookingPaymentSummary::sync($booking);
        $this->assertSame('unpaid', $booking->fresh()->invoice_status);
    }
}

<?php

namespace Tests\Feature;

use App\Mail\BookingGuestUpdateMail;
use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use App\Support\AppSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BookingGuestUpdate;
use Tests\TestCase;

class BookingScheduledInvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_period_booking_can_be_created_when_browser_does_not_submit_period_rows(): void
    {
        Mail::fake();
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => '812', 'category' => '1 BHK', 'status' => 'vacant', 'management_fee_percent' => 10]);

        $this->actingAs($admin)->post(route('admin.booking.store'), [
            'property_id' => $property->id,
            'guest_name' => 'Ranjith Hewalage', 'guest_email' => 'ranjith@example.com', 'guest_phone' => '0500000001',
            'guest_passport_id_no' => 'PASS-812', 'check_in' => '2026-09-26', 'check_out' => '2026-10-25',
            'check_in_time' => '15:00', 'check_out_time' => '11:00',
            'rent_amount' => 6000, 'vat_included' => 0, 'dtcm_fee' => 200,
            'cleaning_fee' => 100, 'agency_fee' => 100, 'security_deposit' => 500,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $booking = Booking::where('guest_email', 'ranjith@example.com')->firstOrFail();
        $this->assertCount(1, $booking->invoices);
        $this->assertSame('2026-09-26', $booking->invoices->first()->period_from->toDateString());
        $this->assertSame('2026-10-25', $booking->invoices->first()->period_to->toDateString());
    }

    public function test_long_booking_creates_separate_period_invoices_and_renews_dtcm_at_day_ninety(): void
    {
        Mail::fake();
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => '502', 'category' => '1 BHK', 'status' => 'vacant', 'management_fee_percent' => 10]);
        AppSettings::setMany(['dtcm_fee_1_bhk' => 300]);

        $this->actingAs($admin)->post(route('admin.booking.store'), [
            'property_id' => $property->id,
            'guest_name' => 'Test Guest', 'guest_email' => 'guest@example.com', 'guest_phone' => '0500000000',
            'guest_passport_id_no' => 'P123456', 'check_in' => '2026-09-01', 'check_out' => '2026-12-10',
            'rent_amount' => 6000, 'vat_included' => 0, 'dtcm_fee' => 0,
            'cleaning_fee' => 200, 'agency_fee' => 100, 'security_deposit' => 500,
            'period_rents' => [6000, 6000, 6000, 2000],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $contracts = Booking::where('guest_email', 'guest@example.com')->orderBy('check_in')->get();
        $this->assertCount(2, $contracts);
        $this->assertSame($contracts[0]->id, $contracts[1]->renewed_from_booking_id);
        $this->assertSame(90, $contracts[0]->nights);
        $this->assertSame(10, $contracts[1]->nights);
        $invoices = $contracts->flatMap(fn ($contract) => $contract->invoices()->orderBy('period_from')->get());
        $this->assertCount(4, $invoices);
        $this->assertSame(['original', 'extension', 'extension', 'renewal'], $invoices->pluck('invoice_type')->all());
        $this->assertSame(['300.00', '0.00', '0.00', '300.00'], $invoices->map(fn ($invoice) => number_format((float) ($invoice->fees['DTCM Fee'] ?? 0), 2, '.', ''))->all());
        $this->assertSame('2026-11-30', $invoices[3]->due_date->toDateString());
        $this->assertSame(500.0, (float) $invoices[0]->fees['Security Deposit']);
        $this->assertSame(0.0, (float) $invoices[1]->fees['Security Deposit']);
        $this->assertSame('315.00', $invoices[0]->vat_amount);
        $this->assertSame('7415.00', $invoices[0]->total_amount);
        $this->assertSame('2400.00', $invoices[3]->total_amount);
        $this->assertEqualsWithDelta($invoices->sum('total_amount'), $contracts->sum('total_amount'), .01);
        Mail::assertSent(BookingGuestUpdateMail::class, fn ($mail) => $mail->event === 'created' && count($mail->schedule) === 4 && filled($mail->temporaryPassword) && str_contains($mail->render(), 'Your guest app access'));
        Notification::assertSentTo(User::findOrFail($contracts[0]->tenant_id), BookingGuestUpdate::class);
    }
}

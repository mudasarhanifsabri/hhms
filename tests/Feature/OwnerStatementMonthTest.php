<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use App\Support\OwnerStatementPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerStatementMonthTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_income_is_reported_in_service_month_not_payment_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => '3308']);
        $booking = Booking::create([
            'property_id' => $unit->id, 'booking_reference' => 'BK-OCT', 'invoice_number' => 'INV-OCT',
            'guest_name' => 'October Guest', 'guest_email' => 'guest@example.com', 'guest_phone' => '123',
            'guest_passport_id_no' => 'P-OCT-1',
            'check_in' => '2026-10-05', 'check_out' => '2026-11-04', 'rent_amount' => 14000,
            'management_fee_percent' => 10, 'status' => 'confirmed', 'invoice_status' => 'paid',
        ]);
        $invoice = BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-20260924-G3KUI', 'invoice_type' => 'original',
            'issue_date' => '2026-09-24', 'period_from' => '2026-10-05', 'period_to' => '2026-11-04',
            'rent_amount' => 14000, 'total_amount' => 14000, 'status' => 'paid',
        ]);
        $rent = LandlordAccountEntry::create([
            'landlord_id' => $owner->id, 'property_id' => $unit->id, 'booking_invoice_id' => $invoice->id,
            'entry_date' => '2026-09-24', 'type' => 'rent_income', 'direction' => 'credit', 'amount' => 14000,
            'description' => 'October rent received',
        ]);
        LandlordAccountEntry::create([
            'landlord_id' => $owner->id, 'property_id' => $unit->id, 'booking_invoice_id' => $invoice->id,
            'entry_date' => '2026-09-24', 'type' => 'management_fee', 'direction' => 'debit', 'amount' => 1400,
            'description' => 'October management fee',
        ]);
        LandlordAccountEntry::create([
            'landlord_id' => $owner->id, 'property_id' => $unit->id, 'entry_date' => '2026-09-20',
            'type' => 'maintenance', 'direction' => 'debit', 'amount' => 250, 'description' => 'September repair',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.landlord.account-statement', [$owner, 'month' => '2026-09']))
            ->assertOk()->assertSee('September repair')->assertDontSee('October rent received');

        $response = $this->get(route('admin.landlord.account-statement', [$owner, 'month' => '2026-10']))
            ->assertOk()->assertSee('October 2026')->assertSee('October rent received')->assertSee('Paid 24 Sep 2026')
            ->assertDontSee('September repair');
        $this->assertSame([$rent->id], $response->viewData('accountEntries')->where('type', 'rent_income')->pluck('id')->all());

        $pdfData = OwnerStatementPdf::data($owner, '2026-10-01', '2026-10-31', $unit->id);
        $this->assertTrue($pdfData['entries']->contains('id', $rent->id));
        $this->assertSame('2026-10-05', $pdfData['entries']->firstWhere('id', $rent->id)->statement_date->toDateString());
    }
}

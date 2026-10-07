<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use App\Support\OwnerStatementPdf;
use App\Support\OwnerReceiptPosting;
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
        $payment = BookingInvoicePayment::create([
            'booking_invoice_id' => $invoice->id, 'payment_date' => '2026-09-24', 'amount' => 14000,
            'rent_amount' => 14000, 'payment_method' => 'Bank Transfer',
        ]);
        $rent = LandlordAccountEntry::create([
            'landlord_id' => $owner->id, 'property_id' => $unit->id,
            'entry_date' => '2026-09-24', 'type' => 'rent_income', 'direction' => 'credit', 'amount' => 14000,
            'reference' => 'PAY-'.$payment->id, 'description' => 'October rent received',
        ]);
        LandlordAccountEntry::create([
            'landlord_id' => $owner->id, 'property_id' => $unit->id,
            'entry_date' => '2026-09-24', 'type' => 'management_fee', 'direction' => 'debit', 'amount' => 1400,
            'reference' => 'PAY-'.$payment->id, 'description' => 'October management fee',
        ]);
        LandlordAccountEntry::create([
            'landlord_id' => $owner->id, 'property_id' => $unit->id, 'entry_date' => '2026-09-20',
            'type' => 'maintenance', 'direction' => 'debit', 'amount' => 250, 'description' => 'September repair',
        ]);

        $migration = require database_path('migrations/071_2026_10_07_link_owner_entries_to_booking_invoices.php');
        $migration->up();
        $this->assertSame($invoice->id, $rent->fresh()->booking_invoice_id);

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

        $this->get(route('admin.landlord.account-statement', [$owner, 'year' => '2026']))
            ->assertOk()->assertSee('September repair')->assertSee('October rent received');
    }

    public function test_new_receipt_postings_store_the_invoice_link(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => 'Future Unit']);
        $booking = Booking::create([
            'property_id' => $unit->id, 'booking_reference' => 'BK-FUTURE', 'invoice_number' => 'INV-FUTURE',
            'guest_name' => 'Future Guest', 'guest_email' => 'future@example.com', 'guest_phone' => '123',
            'guest_passport_id_no' => 'P-FUTURE', 'check_in' => '2026-11-01', 'check_out' => '2026-11-30',
            'rent_amount' => 5000, 'owner_posting_basis' => 'receipts', 'management_fee_percent' => 10,
            'status' => 'confirmed', 'invoice_status' => 'paid',
        ]);
        $invoice = BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-FUTURE', 'invoice_type' => 'original',
            'issue_date' => '2026-10-20', 'period_from' => '2026-11-01', 'period_to' => '2026-11-30',
            'rent_amount' => 5000, 'total_amount' => 5000, 'status' => 'paid',
        ]);
        $payment = BookingInvoicePayment::create([
            'booking_invoice_id' => $invoice->id, 'payment_date' => '2026-10-20', 'amount' => 5000,
            'rent_amount' => 5000, 'payment_method' => 'Bank Transfer',
        ]);

        OwnerReceiptPosting::post($payment->fresh('invoice.booking.property'));

        $this->assertSame(2, LandlordAccountEntry::where('booking_invoice_id', $invoice->id)->count());
        $this->assertSame('2026-11-01', LandlordAccountEntry::where('type', 'rent_income')->firstOrFail()->statement_date->toDateString());
    }
}

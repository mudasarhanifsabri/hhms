<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use App\Support\LegacyOwnerReconciliation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyOwnerReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function legacy(): array
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit 10']);
        $booking = Booking::create(['property_id' => $property->id, 'booking_reference' => 'BK-LEGACY', 'invoice_number' => 'INV-LEGACY',
            'guest_name' => 'Legacy Guest', 'guest_email' => 'guest@example.com', 'guest_phone' => '123', 'guest_passport_id_no' => 'P1',
            'check_in' => '2026-08-01', 'check_out' => '2026-08-10', 'rent_amount' => 1000, 'management_fee_percent' => 10,
            'invoice_status' => 'unpaid', 'status' => 'confirmed']);
        $invoice = BookingInvoice::create(['booking_id' => $booking->id, 'invoice_number' => 'INV-LEGACY', 'invoice_type' => 'original',
            'issue_date' => '2026-08-01', 'rent_amount' => 1000, 'vat_amount' => 50, 'total_amount' => 1050, 'status' => 'unpaid']);
        $base = ['landlord_id' => $owner->id, 'property_id' => $property->id, 'entry_date' => '2026-08-01', 'reference' => 'BK-LEGACY'];
        LandlordAccountEntry::create($base + ['type' => 'rent_income', 'direction' => 'credit', 'amount' => 1000, 'description' => 'Booking rent income for Unit 10']);
        LandlordAccountEntry::create($base + ['type' => 'management_fee', 'direction' => 'debit', 'amount' => 100, 'description' => 'Management fee 10% from Unit 10 rent only']);

        return [$booking, $invoice, $owner];
    }

    public function test_unpaid_entries_are_offset_not_deleted_and_repeat_is_safe(): void
    {
        [$booking, $invoice, $owner] = $this->legacy();
        LandlordAccountEntry::create(['landlord_id' => $owner->id, 'entry_date' => '2026-08-01', 'reference' => 'EXP-1', 'type' => 'furnishing', 'direction' => 'debit', 'amount' => 200]);
        $migration = require database_path('migrations/039_2026_09_03_reconcile_unpaid_legacy_owner_entries.php');
        $migration->up();
        $this->assertSame('receipts', $booking->fresh()->owner_posting_basis);
        $this->assertSame(5, LandlordAccountEntry::count());
        $this->assertEquals(-200, LandlordAccountEntry::get()->sum(fn ($e) => $e->direction === 'credit' ? $e->amount : -$e->amount));
        $this->assertSame(2, LandlordAccountEntry::where('reference', 'BK-LEGACY')->count());
        $this->assertEquals(1050, $invoice->fresh()->balance_due);
        $this->assertSame(0, AccountingEntry::count());
        $migration->up();
        $this->assertSame(5, LandlordAccountEntry::count());
        $this->assertSame(1, $booking->histories()->where('title', 'Owner Posting Reconciled')->count());
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('admin.booking-invoice.payment', $invoice), [
            'amount' => 525, 'rent_amount' => 500, 'payment_date' => '2026-09-03', 'payment_method' => 'Cash'])
            ->assertSessionHasNoErrors();
        $this->assertEquals(250, LandlordAccountEntry::get()->sum(fn ($e) => $e->direction === 'credit' ? $e->amount : -$e->amount));
    }

    public function test_unpaid_status_with_receipt_evidence_is_flagged_and_untouched(): void
    {
        [$booking, $invoice] = $this->legacy();
        $invoice->payments()->create(['amount' => 500, 'payment_date' => today(), 'payment_method' => 'Cash']);
        $result = LegacyOwnerReconciliation::reconcile($booking);
        $this->assertFalse($result['eligible']);
        $this->assertSame('legacy', $booking->fresh()->owner_posting_basis);
        $this->assertSame(2, LandlordAccountEntry::count());
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.booking.history', $booking))->assertOk()->assertSee('Verify rent allocations');
    }

    public function test_ledger_and_manual_entries_block_automatic_conversion(): void
    {
        [$booking] = $this->legacy();
        AccountingEntry::create(['entry_no' => 'OLD-PAY', 'entry_date' => today(), 'type' => 'income', 'booking_id' => $booking->id, 'credit' => 100, 'approval_status' => 'posted']);
        $this->assertFalse(LegacyOwnerReconciliation::inspect($booking)['eligible']);
        $this->artisan('bookings:reconcile-owner-postings')->expectsOutputToContain('requires review: 1')->assertSuccessful();
        $this->assertSame(2, LandlordAccountEntry::count());
    }
}

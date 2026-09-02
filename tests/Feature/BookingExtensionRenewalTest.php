<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingExtensionRenewalTest extends TestCase
{
    use RefreshDatabase;

    private function booking(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit 3308', 'status' => 'rented', 'management_fee_percent' => 10]);
        $booking = Booking::create([
            'property_id' => $property->id, 'booking_reference' => 'BK-EXT-001', 'invoice_number' => 'INV-EXT-BASE',
            'guest_name' => 'John Smith', 'guest_email' => 'john@example.com', 'guest_phone' => '0500000000',
            'guest_passport_id_no' => 'P123', 'check_in' => '2026-01-01', 'check_out' => '2026-03-22',
            'rent_amount' => 18000, 'vat_amount' => 900, 'total_amount' => 18900, 'status' => 'confirmed', 'invoice_status' => 'unpaid',
        ]);
        BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-EXT-BASE', 'invoice_type' => 'original',
            'issue_date' => '2026-01-01', 'period_from' => '2026-01-01', 'period_to' => '2026-03-22',
            'rent_amount' => 18000, 'vat_amount' => 900, 'total_amount' => 18900, 'status' => 'unpaid',
        ]);

        return compact('admin', 'booking', 'property');
    }

    public function test_extension_keeps_original_financials_and_creates_separate_invoice(): void
    {
        ['admin' => $admin, 'booking' => $booking] = $this->booking();

        $this->actingAs($admin)->post(route('admin.booking.extend', $booking), [
            'check_out' => '2026-03-27', 'extension_rent_amount' => 4500, 'vat_rate' => 5, 'other_fees' => 100,
        ])->assertSessionHasNoErrors();

        $this->assertSame('18000.00', $booking->fresh()->rent_amount);
        $invoice = BookingInvoice::where('invoice_type', 'extension')->firstOrFail();
        $this->assertSame('4500.00', $invoice->rent_amount);
        $this->assertSame('225.00', $invoice->vat_amount);
        $this->assertSame('4825.00', $invoice->total_amount);
    }

    public function test_extension_beyond_90_days_requires_renewal(): void
    {
        ['admin' => $admin, 'booking' => $booking] = $this->booking();

        $this->actingAs($admin)->post(route('admin.booking.extend', $booking), [
            'check_out' => '2026-04-05', 'extension_rent_amount' => 4500, 'vat_rate' => 5,
        ])->assertSessionHasErrors('check_out');

        $this->assertDatabaseMissing('booking_invoices', ['invoice_type' => 'extension']);
    }

    public function test_invoice_payment_is_separate_and_supports_partial_status(): void
    {
        ['admin' => $admin, 'booking' => $booking] = $this->booking();
        $invoice = $booking->invoices()->firstOrFail();
        $bank = BankAccount::create(['name' => 'Operating Bank', 'type' => 'bank', 'currency' => 'AED', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true]);
        $this->assertNotNull(AccountingAccount::where('code', '4010')->first());

        $this->actingAs($admin)->post(route('admin.booking-invoice.payment', $invoice), [
            'payment_date' => '2026-03-01', 'amount' => 10000, 'payment_method' => 'Bank Transfer', 'bank_account_id' => $bank->id,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('booking_invoice_payments', ['booking_invoice_id' => $invoice->id, 'amount' => 10000]);
        $this->assertSame('partial', $invoice->fresh()->status);
        $this->assertSame('10000.00', $bank->fresh()->current_balance);
    }
}

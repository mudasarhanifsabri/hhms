<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCorrectionsTest extends TestCase
{
    use RefreshDatabase;

    private function setupInvoice(float $deposit = 0): array
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => 'U100', 'management_fee_percent' => 10]);
        $this->post(route('admin.booking.store'), ['property_id' => $unit->id, 'guest_name' => 'Test Guest',
            'guest_email' => 'guest@example.com', 'guest_phone' => '0500000000', 'guest_passport_id_no' => 'G001',
            'check_in' => '2026-10-01', 'check_out' => '2026-10-10', 'rent_amount' => 1000, 'security_deposit' => $deposit])
            ->assertSessionHasNoErrors();
        $booking = Booking::firstOrFail();
        $invoice = $booking->invoices()->firstOrFail();
        $bank = BankAccount::create(['name' => 'Bank', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0, 'currency' => 'AED', 'is_active' => true]);

        return [$booking, $invoice, $bank, $owner];
    }

    private function pay($invoice, $bank, float $amount, float $rent, float $deposit = 0)
    {
        return $this->post(route('admin.booking-invoice.payment', $invoice), ['amount' => $amount, 'rent_amount' => $rent,
            'deposit_amount' => $deposit, 'payment_date' => today()->toDateString(), 'payment_method' => 'Cash', 'bank_account_id' => $bank->id]);
    }

    public function test_booking_posts_no_owner_income_until_actual_rent_is_collected(): void
    {
        [$booking, $invoice, $bank, $owner] = $this->setupInvoice(300);
        $this->assertSame('receipts', $booking->owner_posting_basis);
        $this->assertSame(0, LandlordAccountEntry::count());
        $this->pay($invoice, $bank, 675, 500, 150)->assertSessionHasNoErrors();
        $this->assertEquals(500, LandlordAccountEntry::where('type', 'rent_income')->sum('amount'));
        $this->assertEquals(50, LandlordAccountEntry::where('type', 'management_fee')->sum('amount'));
        $this->assertEquals(150, \App\Support\DepositWallet::totals($booking)['held']);
        $this->assertEquals(675, $bank->fresh()->current_balance);
        $this->pay($invoice, $bank, 675, 675)->assertSessionHasErrors('rent_amount');
        $this->pay($invoice, $bank, 675, 500, 150)->assertSessionHasNoErrors();
        $this->assertEquals(1000, LandlordAccountEntry::where('type', 'rent_income')->sum('amount'));
        $this->assertSame('paid', $invoice->fresh()->status);
        $payment = $invoice->payments()->firstOrFail();
        $this->post(route('admin.booking-payment.reverse', $payment), ['reason' => 'Mistaken amount', 'confirm' => 1])->assertSessionHasErrors('correction');
        $this->get(route('admin.booking.history', $booking))->assertOk()->assertSee('Edit Payment Details');
    }

    public function test_unpaid_invoice_can_be_corrected_but_paid_one_is_locked(): void
    {
        [$booking, $invoice, $bank] = $this->setupInvoice();
        $data = ['rent_amount' => 2000, 'vat_rate' => 5, 'fees' => $invoice->fees, 'reason' => 'Correct agreed rent'];
        $this->put(route('admin.booking-invoice.correct', $invoice), $data)->assertSessionHasNoErrors();
        $this->assertEquals(2100, $invoice->fresh()->total_amount);
        $this->assertEquals(2000, $booking->fresh()->rent_amount);
        $this->assertEquals(0, LandlordAccountEntry::count());
        $this->pay($invoice, $bank, 1050, 1000)->assertSessionHasNoErrors();
        $this->put(route('admin.booking-invoice.correct', $invoice), $data)->assertSessionHasErrors('correction');
        $this->get(route('admin.booking.history', $booking))->assertOk()->assertSee('Invoice Corrected');
    }

    public function test_reversal_preserves_history_reopens_balance_and_reverses_owner_and_bank(): void
    {
        [$booking, $invoice, $bank] = $this->setupInvoice();
        $this->pay($invoice, $bank, 525, 500)->assertSessionHasNoErrors();
        $payment = $invoice->payments()->firstOrFail();
        $this->put(route('admin.booking-payment.details', $payment), ['reference' => 'CORRECTED-REF', 'notes' => 'Updated', 'reason' => 'Correct reference'])->assertSessionHasNoErrors();
        $this->assertSame('CORRECTED-REF', $payment->fresh()->reference);
        $this->post(route('admin.booking-payment.reverse', $payment), ['reason' => 'Wrong recorded amount', 'confirm' => 1])->assertSessionHasNoErrors();
        $this->assertNotNull($payment->fresh()->reversed_at);
        $this->assertEquals(0, $bank->fresh()->current_balance);
        $this->assertEquals(1050, $invoice->fresh()->balance_due);
        $this->assertEquals(0, LandlordAccountEntry::get()->sum(fn ($e) => $e->direction === 'credit' ? $e->amount : -$e->amount));
        $this->assertSame(2, AccountingEntry::count());
        $this->post(route('admin.booking-payment.reverse', $payment), ['reason' => 'Repeated reversal', 'confirm' => 1])->assertSessionHasErrors('correction');
        $this->assertSame(2, AccountingEntry::count());
        $this->pay($invoice, $bank, 1050, 1000)->assertSessionHasNoErrors();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(2, $invoice->allPayments()->count());
        $this->get(route('admin.booking.history', $booking))->assertOk()->assertSee('Payment Reversed');
        $this->delete(route('admin.booking.destroy', $booking))->assertSessionHasErrors('payment');
    }

    public function test_owner_cannot_correct_invoices_or_payments_and_proof_cannot_fake_payment(): void
    {
        [$booking, $invoice, $bank, $owner] = $this->setupInvoice();
        $this->post(route('admin.booking.payment-proof', $booking))->assertSessionHasErrors('payment');
        $this->assertEquals(0, AccountingEntry::count());
        $this->actingAs($owner)->put(route('admin.booking-invoice.correct', $invoice), [])->assertForbidden();
    }
}

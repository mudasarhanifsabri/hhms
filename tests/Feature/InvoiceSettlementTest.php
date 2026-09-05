<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
use App\Models\Property;
use App\Models\User;
use App\Support\DepositWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSettlementTest extends TestCase
{
    use RefreshDatabase;

    private function setupInvoice(float $deposit = 300): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $agent = User::factory()->create(['role' => 'agent', 'agent_commission' => 25]);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => 'Settlement Unit']);
        $booking = Booking::create(['property_id' => $unit->id, 'agent_id' => $agent->id, 'booking_reference' => 'BK-SETTLE', 'invoice_number' => 'INV-SETTLE',
            'guest_name' => 'Guest', 'guest_email' => 'guest@example.com', 'guest_phone' => '123', 'guest_passport_id_no' => 'P123', 'check_in' => '2026-09-01', 'check_out' => '2026-09-30',
            'rent_amount' => 1000, 'owner_posting_basis' => 'receipts', 'management_fee_percent' => 10, 'status' => 'confirmed', 'invoice_status' => 'unpaid']);
        $invoice = BookingInvoice::create(['booking_id' => $booking->id, 'invoice_number' => 'INV-SETTLE', 'invoice_type' => 'original', 'issue_date' => '2026-09-01', 'period_from' => '2026-09-01', 'period_to' => '2026-09-30',
            'rent_amount' => 1000, 'vat_amount' => 50, 'vat_rate' => 5, 'fees' => ['Cleaning Fee' => 100, 'Agency Fee' => 200, 'DTCM Fee' => 30, 'Security Deposit' => $deposit], 'total_amount' => 1380 + $deposit, 'status' => 'unpaid']);
        $bank = BankAccount::create(['name' => 'Settlement Bank', 'type' => 'bank', 'currency' => 'AED', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true]);
        $this->actingAs($admin);

        return [$booking, $invoice, $bank, $agent];
    }

    private function pay(BookingInvoice $invoice, BankAccount $bank, ?float $amount = null)
    {
        return $this->post(route('admin.booking-invoice.payment', $invoice), ['payment_date' => '2026-09-03', 'amount' => $amount ?? $invoice->total_amount, 'bank_account_id' => $bank->id, 'payment_method' => 'Bank Transfer', 'rent_amount' => 1, 'deposit_amount' => 1]);
    }

    public function test_full_payment_splits_fees_without_duplicate_bank_cash_and_unlocks_confirmation(): void
    {
        [$booking, $invoice, $bank, $agent] = $this->setupInvoice();
        $this->get(route('admin.booking-invoice.confirmation', $invoice))->assertStatus(422);
        $this->get(route('guest.booking.confirmation', $booking->booking_reference))->assertStatus(422);
        $this->pay($invoice, $bank)->assertSessionHasNoErrors();
        $payment = BookingInvoicePayment::sole();
        $this->assertSame('1000.00', $payment->rent_amount);
        $this->assertEquals(50, $payment->allocation['agent_commission']);
        $this->assertEquals(150, $payment->allocation['agency_company_share']);
        $this->assertEquals(100, $payment->allocation['cleaning']);
        $this->assertEquals(300, DepositWallet::totals($booking)['held']);
        $this->assertSame('1680.00', $bank->fresh()->current_balance);
        $income = AccountingEntry::whereHas('accountingAccount', fn ($q) => $q->where('type', 'income'))->selectRaw('SUM(credit - debit) as total')->value('total');
        $this->assertEquals(350, $income);
        $this->assertDatabaseHas('landlord_account_entries', ['reference' => 'PAY-'.$payment->id, 'type' => 'rent_income', 'amount' => 1000]);
        $this->assertDatabaseHas('landlord_account_entries', ['reference' => 'PAY-'.$payment->id, 'type' => 'management_fee', 'amount' => 100]);
        $this->get(route('admin.booking-invoice.confirmation', $invoice))->assertOk();
        $this->get(route('guest.booking.confirmation', $booking->booking_reference))->assertOk();
        $this->pay($invoice, $bank)->assertSessionHasErrors('amount');
        $this->assertSame(1, BookingInvoicePayment::count());
        $this->put(route('admin.booking.agent-commission', $booking), ['agent_commission_percent' => 90, 'reason' => 'Rate change'])->assertSessionHasErrors('agent_commission_percent');
    }

    public function test_partial_payments_are_itemised_and_booking_override_is_applied(): void
    {
        [$booking, $invoice, $bank] = $this->setupInvoice(0);
        $this->put(route('admin.booking.agent-commission', $booking), ['agent_commission_percent' => 10, 'reason' => 'Agreed special rate'])->assertSessionHasNoErrors();
        $this->pay($invoice, $bank, 500)->assertSessionHasNoErrors();
        $first = BookingInvoicePayment::sole();
        $this->assertSame('partial', $invoice->fresh()->status);
        $this->assertEquals(500, $bank->fresh()->current_balance);
        $this->assertEquals(500, array_sum(collect($first->allocation)->only(['rent', 'vat', 'deposit', 'cleaning', 'agency', 'tourism', 'other'])->all()));
        $this->get(route('admin.booking-invoice.confirmation', $invoice))->assertStatus(422);
        $this->pay($invoice->fresh(), $bank, 880)->assertSessionHasNoErrors();
        $this->assertSame(2, BookingInvoicePayment::count());
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('1380.00', $bank->fresh()->current_balance);
        $this->get(route('admin.booking-invoice.confirmation', $invoice))->assertOk();
        $this->post(route('admin.booking-payment.reverse', $first), ['confirm' => 1, 'reason' => 'Incorrect receipt'])->assertSessionHasNoErrors();
        $this->assertSame('880.00', $bank->fresh()->current_balance);
        $this->get(route('admin.booking-invoice.confirmation', $invoice))->assertStatus(422);
    }

    public function test_status_only_and_legacy_partial_cannot_bypass_payment_rules(): void
    {
        [$booking, $invoice, $bank] = $this->setupInvoice();
        $invoice->update(['status' => 'paid']);
        $this->get(route('admin.booking-invoice.confirmation', $invoice))->assertStatus(422);
        $invoice->update(['status' => 'partial']);
        $invoice->payments()->create(['amount' => 100, 'payment_date' => today(), 'payment_method' => 'Cash']);
        $this->pay($invoice, $bank, 1580)->assertSessionHasErrors('amount');
        $this->assertSame(1, BookingInvoicePayment::count());
    }
}

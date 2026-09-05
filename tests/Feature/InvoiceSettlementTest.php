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
        $this->get(route('guest.booking.show', $booking->booking_reference))->assertOk()
            ->assertSee('Partial')->assertSee('AED 880.00')->assertSee('Payments received')
            ->assertSee('Confirmation available after full payment.');
        $tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true, 'tenant_profile_required' => false]);
        $booking->update(['tenant_id' => $tenant->id]);
        $this->actingAs($tenant)->get(route('tenant.booking.show', $booking))->assertOk()
            ->assertSee('Invoices & Payments', false)->assertSee('AED 500.00')->assertSee('AED 880.00');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->pay($invoice->fresh(), $bank, 880)->assertSessionHasNoErrors();
        $this->assertSame(2, BookingInvoicePayment::count());
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('1380.00', $bank->fresh()->current_balance);
        $this->get(route('admin.booking-invoice.confirmation', $invoice))->assertOk();
        $this->get(route('guest.booking.show', $booking->booking_reference))->assertOk()
            ->assertSee('Download full booking confirmation')->assertSee('AED 0.00');
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

    public function test_one_bank_transfer_is_allocated_across_multiple_invoices(): void
    {
        [$booking, $first, $bank] = $this->setupInvoice(0);
        $second = BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-SETTLE-EXT', 'invoice_type' => 'extension',
            'issue_date' => '2026-10-01', 'period_from' => '2026-10-01', 'period_to' => '2026-10-15',
            'rent_amount' => 500, 'vat_amount' => 25, 'vat_rate' => 5, 'fees' => [], 'total_amount' => 525, 'status' => 'unpaid',
        ]);
        $this->get(route('admin.booking.show', $booking))->assertOk()->assertSee('Combined payment');

        $payload = [
            'payment_date' => '2026-10-02', 'amount' => 1600, 'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bank->id, 'reference' => 'BANK-COMBINED-001', 'submission_id' => (string) \Illuminate\Support\Str::uuid(),
        ];
        $this->post(route('admin.booking.combined-payment', $booking), $payload)->assertSessionHasNoErrors();

        $payments = BookingInvoicePayment::orderBy('created_at')->get();
        $this->assertCount(2, $payments);
        $this->assertEquals(1380, (float) $payments[0]->amount);
        $this->assertEquals(220, (float) $payments[1]->amount);
        $this->assertNotNull($payments[0]->payment_batch_id);
        $this->assertSame($payments[0]->payment_batch_id, $payments[1]->payment_batch_id);
        $this->assertSame($payments[0]->accounting_entry_id, $payments[1]->accounting_entry_id);
        $this->assertSame('paid', $first->fresh()->status);
        $this->assertSame('partial', $second->fresh()->status);
        $this->assertEquals(305, $second->fresh()->balance_due);
        $this->assertSame('1600.00', $bank->fresh()->current_balance);
        $this->assertSame(1, AccountingEntry::where('category', 'guest_receipt')->count());
        $this->assertDatabaseHas('booking_payment_batches', ['id' => $payload['submission_id'], 'amount' => 1600]);
        $this->post(route('admin.booking.combined-payment', $booking), $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, BookingInvoicePayment::count());
        $this->assertSame(1, AccountingEntry::where('category', 'guest_receipt')->count());
        $this->post(route('admin.booking-payment.reverse', $payments[0]), ['confirm' => 1, 'reason' => 'Wrong combined transfer'])->assertSessionHasErrors('correction');
    }
}

<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\Building;
use App\Models\FinancialApprovalRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function context(float $deposit = 0): array
    {
        $maker = User::factory()->create(['role' => 'admin']);
        $maker->syncRoles(['Accounting']);
        $manager = User::factory()->create(['role' => 'admin']);
        $manager->syncRoles(['Manager']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $building = Building::create(['building_name' => 'Approval Tower', 'address' => 'Dubai']);
        $unit = Property::create(['landlord_id' => $owner->id, 'building_id' => $building->id, 'name' => 'Approval Unit', 'management_fee_percent' => 10]);
        $this->actingAs($maker)->post(route('admin.booking.store'), [
            'property_id' => $unit->id, 'guest_name' => 'Approval Guest', 'guest_email' => 'guest@example.com',
            'guest_phone' => '0500000000', 'guest_passport_id_no' => 'APP-1', 'check_in' => '2026-10-10',
            'check_out' => '2026-11-09', 'reservation_date' => '2026-10-08', 'rent_amount' => 1000, 'security_deposit' => $deposit,
        ])->assertSessionHasNoErrors();
        $booking = Booking::firstOrFail();
        $bank = BankAccount::create(['name' => 'ADCB', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0, 'currency' => 'AED', 'is_active' => true]);
        return [$maker, $manager, $booking, $booking->invoices()->firstOrFail(), $bank];
    }

    public function test_payment_changes_nothing_until_a_different_manager_approves(): void
    {
        [$maker, $manager, $booking, $invoice, $bank] = $this->context();
        $response = $this->actingAs($maker)->post(route('admin.booking-invoice.payment', $invoice), [
            'payment_date' => '2026-10-08', 'amount' => $invoice->total_amount, 'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bank->id, 'reference' => 'ADCB-APPROVAL-001',
        ]);
        $response->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');

        $approval = FinancialApprovalRequest::firstOrFail();
        $this->assertSame('pending', $approval->status);
        $this->assertSame((string)$maker->id, $approval->requested_by);
        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(0, $invoice->payments()->count());
        $this->assertEquals(0, $bank->fresh()->current_balance);

        $this->actingAs($maker)->post(route('admin.financial-approvals.approve', $approval))->assertForbidden();
        $this->actingAs($manager)->post(route('admin.financial-approvals.approve', $approval), ['review_notes' => 'Bank proof and reference verified'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('approved', $approval->fresh()->status);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertEquals((float)$invoice->total_amount, (float)$bank->fresh()->current_balance);
        $this->assertSame('ADCB-APPROVAL-001', $invoice->payments()->first()->reference);
    }

    public function test_admin_can_view_details_but_only_manager_can_decide(): void
    {
        [$maker, $manager, $booking, $invoice, $bank] = $this->context();
        $this->actingAs($maker)->post(route('admin.booking-invoice.payment', $invoice), [
            'payment_date' => '2026-10-08', 'amount' => $invoice->total_amount, 'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bank->id, 'reference' => 'ADCB-MANAGER-ONLY-001',
        ]);
        $approval = FinancialApprovalRequest::firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles(['Admin']);

        $this->actingAs($admin)->get(route('admin.financial-approvals.index'))->assertOk()
            ->assertSee($booking->property->name)->assertSee('Approval Tower')->assertSee('Invoice Period')
            ->assertSee('10 Oct 2026')->assertSee('09 Nov 2026')->assertSee('View more');
        $this->post(route('admin.financial-approvals.approve', $approval))->assertForbidden();
        $this->actingAs($manager)->post(route('admin.financial-approvals.approve', $approval))->assertSessionHasNoErrors();
        $this->assertSame('approved', $approval->fresh()->status);
    }

    public function test_rejection_keeps_invoice_and_bank_unchanged(): void
    {
        [$maker, $manager, , $invoice, $bank] = $this->context();
        $this->actingAs($maker)->post(route('admin.booking-invoice.payment', $invoice), [
            'payment_date' => '2026-10-08', 'amount' => $invoice->total_amount, 'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bank->id, 'reference' => 'ADCB-REJECT-001',
        ]);
        $approval = FinancialApprovalRequest::firstOrFail();
        $this->actingAs($manager)->post(route('admin.financial-approvals.reject', $approval), ['review_notes' => 'Amount is not visible in ADCB'])
            ->assertSessionHasNoErrors();
        $this->assertSame('rejected', $approval->fresh()->status);
        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(0, $invoice->payments()->count());
        $this->assertEquals(0, $bank->fresh()->current_balance);
    }

    public function test_manager_dashboard_shows_and_can_review_pending_payment_action(): void
    {
        [$maker, , , $invoice, $bank] = $this->context();
        $this->actingAs($maker)->post(route('admin.booking-invoice.payment', $invoice), [
            'payment_date' => '2026-10-08', 'amount' => $invoice->total_amount, 'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bank->id, 'reference' => 'ADCB-MANAGER-001',
        ]);
        $approval = FinancialApprovalRequest::firstOrFail();
        $manager = User::factory()->create(['role' => 'admin']);
        $manager->syncRoles(['Manager']);

        $this->actingAs($manager)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Pending Financial Approvals')->assertSee($approval->approval_no);
        $this->get(route('admin.financial-approvals.index'))->assertOk()->assertSee($approval->approval_no);
        $this->post(route('admin.financial-approvals.approve', $approval))->assertSessionHasNoErrors();
        $this->assertSame('approved', $approval->fresh()->status);
    }

    public function test_payment_edit_and_deletion_wait_for_manager_approval(): void
    {
        [$maker, $manager, , $invoice, $bank] = $this->context();
        $this->actingAs($maker)->post(route('admin.booking-invoice.payment', $invoice), [
            'payment_date' => '2026-10-08', 'amount' => $invoice->total_amount, 'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bank->id, 'reference' => 'ADCB-CORRECT-001',
        ]);
        $create = FinancialApprovalRequest::firstOrFail();
        $this->actingAs($manager)->post(route('admin.financial-approvals.approve', $create));
        $payment = $invoice->payments()->firstOrFail();

        $backend = User::factory()->create(['role' => 'admin']);
        $backend->syncRoles(['Backend IT']);
        $this->actingAs($backend)->put(route('admin.booking-payment.details', $payment), [
            'reference' => 'ADCB-CORRECT-002', 'notes' => 'Correct bank narration', 'reason' => 'Reference entered incorrectly',
        ])->assertSessionHasNoErrors();
        $edit = FinancialApprovalRequest::where('type', 'payment_edit')->firstOrFail();
        $this->assertSame('ADCB-CORRECT-001', $payment->fresh()->reference);
        $this->actingAs($manager)->post(route('admin.financial-approvals.approve', $edit))->assertSessionHasNoErrors();
        $this->assertSame('ADCB-CORRECT-002', $payment->fresh()->reference);

        $this->actingAs($backend)->post(route('admin.booking-payment.reverse', $payment), [
            'reason' => 'Payment was posted against wrong booking', 'confirm' => 1,
        ])->assertSessionHasNoErrors();
        $deletion = FinancialApprovalRequest::where('type', 'payment_reverse')->firstOrFail();
        $this->assertNull($payment->fresh()->reversed_at);
        $this->assertSame('paid', $invoice->fresh()->status);
        $historyText = $invoice->booking->histories()->where('title', 'Payment Deletion Approval Requested')->firstOrFail()->display_description;
        $this->assertStringContainsString($deletion->approval_no, $historyText);
        $this->assertStringContainsString($payment->transaction_no, $historyText);
        $this->assertStringNotContainsString($payment->id, $historyText);
        $this->actingAs($manager)->post(route('admin.financial-approvals.approve', $deletion))->assertSessionHasNoErrors();
        $this->assertNotNull($payment->fresh()->reversed_at);
        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertEquals(0, $bank->fresh()->current_balance);
    }

    public function test_approved_deletion_safely_reverses_a_deposit_linked_payment(): void
    {
        [$maker, $reviewer, $booking, $invoice, $bank] = $this->context(500);
        $this->actingAs($maker)->post(route('admin.booking-invoice.payment', $invoice), [
            'payment_date' => '2026-10-08', 'amount' => $invoice->total_amount, 'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bank->id, 'reference' => 'ADCB-DEPOSIT-001',
        ]);
        $this->actingAs($reviewer)->post(route('admin.financial-approvals.approve', FinancialApprovalRequest::firstOrFail()))->assertSessionHasNoErrors();
        $payment = $invoice->payments()->firstOrFail();
        $this->assertEquals(500, \App\Support\DepositWallet::totals($booking)['held']);

        $backend = User::factory()->create(['role' => 'admin']);
        $backend->syncRoles(['Backend IT']);
        $this->actingAs($backend)->post(route('admin.booking-payment.reverse', $payment), [
            'reason' => 'Wrong receipt including deposit', 'confirm' => 1,
        ])->assertSessionHasNoErrors();
        $deletion = FinancialApprovalRequest::where('type', 'payment_reverse')->firstOrFail();
        $this->actingAs($reviewer)->post(route('admin.financial-approvals.approve', $deletion))->assertSessionHasNoErrors();

        $this->assertNotNull($payment->fresh()->reversed_at);
        $this->assertEquals(0, \App\Support\DepositWallet::totals($booking)['held']);
        $this->assertEquals(0, $bank->fresh()->current_balance);
        $this->assertSame('unpaid', $invoice->fresh()->status);
    }
}

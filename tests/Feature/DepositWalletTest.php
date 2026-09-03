<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\BookingDepositEntry;
use App\Models\BookingDepositRefund;
use App\Models\BookingInvoice;
use App\Models\Property;
use App\Models\User;
use App\Support\DepositWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DepositWalletTest extends TestCase
{
    use RefreshDatabase;

    private function setupBooking(bool $allocate = true): array
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => '3308', 'status' => 'rented']);
        $booking = Booking::create(['property_id' => $property->id, 'booking_reference' => 'BK-DEPOSIT', 'invoice_number' => 'INV-DEPOSIT', 'guest_name' => 'John Smith', 'guest_email' => 'john@example.com', 'guest_phone' => '0500000000', 'guest_passport_id_no' => 'P-1234', 'check_in' => '2026-08-01', 'check_out' => '2026-09-01', 'rent_amount' => 1000, 'security_deposit' => 1500, 'total_amount' => 2500, 'status' => 'confirmed']);
        $invoice = BookingInvoice::create(['booking_id' => $booking->id, 'invoice_number' => 'INV-DEPOSIT', 'invoice_type' => 'original', 'issue_date' => today(), 'rent_amount' => 1000, 'vat_amount' => 0, 'fees' => ['Security Deposit' => 1500], 'total_amount' => 2500, 'status' => 'unpaid']);
        $bank = BankAccount::create(['name' => 'Operating Bank', 'type' => 'bank', 'currency' => 'AED', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.booking-invoice.payment', $invoice), ['payment_date' => today()->toDateString(), 'amount' => 2500, 'deposit_amount' => $allocate ? 1500 : 0, 'payment_method' => 'Bank Transfer', 'bank_account_id' => $bank->id])->assertSessionHasNoErrors()->assertSessionHas('success');

        return compact('admin', 'owner', 'booking', 'invoice', 'bank');
    }

    private function request(Booking $booking, bool $deduction = false): BookingDepositRefund
    {
        $data = ['reason' => 'Guest departure refund'];
        if ($deduction) {
            $data['deductions'] = [['description' => 'Broken lamp', 'amount' => 200, 'evidence' => UploadedFile::fake()->create('damage.pdf', 10, 'application/pdf')]];
        }
        $this->post(route('admin.booking.deposit.request', $booking), $data)->assertSessionHasNoErrors()->assertSessionHas('success');

        return BookingDepositRefund::where('booking_id', $booking->id)->latest()->firstOrFail();
    }

    private function payout(BankAccount $bank, float $amount, ?string $submission = null): array
    {
        return ['amount' => $amount, 'entry_date' => today()->toDateString(), 'bank_account_id' => $bank->id, 'payment_method' => 'Bank Transfer', 'recipient' => 'John Smith', 'reference' => 'TRF-REFUND-001', 'proof' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf'), 'submission_id' => $submission ?? (string) Str::uuid()];
    }

    public function test_booking_list_and_grid_share_search_filters_and_pagination(): void
    {
        ['booking' => $booking] = $this->setupBooking();
        foreach (['admin.booking.index', 'admin.booking.grid'] as $route) {
            $this->get(route($route, ['search' => 'John', 'status' => 'confirmed', 'invoice_status' => 'paid', 'from' => '2026-08-01', 'to' => '2026-08-01', 'per_page' => 25]))
                ->assertOk()->assertViewHas('bookings', fn ($rows) => $rows->total() === 1 && $rows->perPage() === 25)
                ->assertSee($booking->booking_reference)->assertSee('Apply Filters');
            $this->get(route($route, ['search' => 'no-such-guest']))->assertOk()
                ->assertViewHas('bookings', fn ($rows) => $rows->total() === 0);
            $this->get(route($route, ['status' => 'checked_out']))->assertOk()
                ->assertViewHas('bookings', fn ($rows) => $rows->total() === 0);
            $this->getJson(route($route, ['per_page' => 100000]))->assertUnprocessable();
            $this->getJson(route($route, ['from' => '2026-09-01', 'to' => '2026-08-01']))->assertUnprocessable();
        }
    }

    public function test_wallet_actions_are_in_popups_with_summary_and_tables(): void
    {
        ['booking' => $booking] = $this->setupBooking();
        $this->get(route('admin.booking.deposit-wallet', $booking))->assertOk()
            ->assertSee('Full Refund')->assertSee('With Deductions')
            ->assertSee('Linked Invoices')->assertSee('View Audit Log');
        $refund = $this->request($booking);
        foreach (['pending', 'approved'] as $status) {
            if ($status === 'approved') {
                $this->post(route('admin.booking.deposit.review', [$booking, $refund]), ['decision' => 'approved', 'review_notes' => 'Checked for payment'])->assertSessionHasNoErrors();
            }
            $response = $this->get(route('admin.booking.deposit-wallet', $booking))->assertOk();
            $document = new \DOMDocument;
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML($response->getContent());
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $xpath = new \DOMXPath($document);
            $this->assertSame(2, $xpath->query('//table[@id="refundTable" or @id="depositTable"]')->length);
            $forms = $xpath->query('//form[contains(@action,"deposit-wallet")]');
            $this->assertGreaterThanOrEqual(3, $forms->length);
            foreach ($forms as $form) {
                $this->assertSame(1, $xpath->query('ancestor::div[contains(concat(" ",normalize-space(@class)," ")," modal ")]', $form)->length);
            }
            foreach ($xpath->query('//button[@data-bs-target and not(@disabled)]') as $button) {
                $target = substr($button->getAttribute('data-bs-target'), 1);
                $this->assertNotNull($document->getElementById($target), 'Missing popup: '.$target);
            }
        }
    }

    public function test_allocation_does_not_collect_twice_or_change_invoice(): void
    {
        ['booking' => $booking, 'invoice' => $invoice, 'bank' => $bank] = $this->setupBooking(false);
        $payment = $invoice->payments()->firstOrFail();
        $payload = ['payment_id' => $payment->id, 'amount' => 1500, 'submission_id' => (string) Str::uuid()];
        $this->post(route('admin.booking.deposit.allocate', $booking), $payload)->assertSessionHasNoErrors();
        $this->post(route('admin.booking.deposit.allocate', $booking), $payload)->assertSessionHasNoErrors();
        $this->assertSame(1500.0, DepositWallet::totals($booking)['held']);
        $this->assertSame(1, BookingDepositEntry::count());
        $this->assertSame(2500.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(2500.0, (float) $invoice->fresh()->total_amount);
        $this->assertSame(1000.0, (float) AccountingEntry::where('type', 'income')->sum('credit'));
        $this->post(route('admin.booking.deposit.allocate', $booking), [...$payload, 'amount' => 1, 'submission_id' => (string) Str::uuid()])->assertSessionHasErrors('deposit');
        $this->get(route('admin.booking.deposit-wallet', $booking))->assertOk()->assertSee('Security Deposit Wallet');
    }

    public function test_approval_deduction_and_partial_refunds_are_separate_and_capped(): void
    {
        ['booking' => $booking, 'bank' => $bank] = $this->setupBooking();
        $refund = $this->request($booking, true);
        $this->post(route('admin.booking.deposit.pay', [$booking, $refund]), $this->payout($bank, 500))->assertSessionHasErrors('deposit');
        $this->post(route('admin.booking.deposit.review', [$booking, $refund]), ['decision' => 'approved', 'review_notes' => 'Evidence checked'])->assertSessionHasNoErrors();
        $this->assertSame(1300.0, DepositWallet::totals($booking)['held']);
        $this->assertSame(2500.0, (float) $bank->fresh()->current_balance);
        $submission = (string) Str::uuid();
        $this->post(route('admin.booking.deposit.pay', [$booking, $refund]), $this->payout($bank, 500, $submission))->assertSessionHasNoErrors();
        $this->post(route('admin.booking.deposit.pay', [$booking, $refund]), $this->payout($bank, 500, $submission))->assertSessionHasNoErrors();
        $this->assertSame(800.0, $refund->fresh()->remaining_amount);
        $this->assertSame(2000.0, (float) $bank->fresh()->current_balance);
        $this->post(route('admin.booking.deposit.pay', [$booking, $refund]), $this->payout($bank, 800.01))->assertSessionHasErrors('deposit');
        $this->post(route('admin.booking.deposit.pay', [$booking, $refund]), $this->payout($bank, 800))->assertSessionHasNoErrors();
        $this->assertSame('settled', $refund->fresh()->status);
        $this->assertSame(0.0, DepositWallet::totals($booking)['held']);
        $this->assertSame(1200.0, (float) $bank->fresh()->current_balance);
        $entry = BookingDepositEntry::where('kind', 'refunded')->firstOrFail();
        $response = $this->get(route('admin.booking.deposit.receipt', [$booking, $entry]))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        if (getenv('HHMS_PDF_QA') === '1') {
            File::ensureDirectoryExists(base_path('tmp/pdfs'));
            File::put(base_path('tmp/pdfs/deposit-refund-qa.pdf'), $response->getContent());
        }
        $this->get(route('admin.booking.deposit-wallet', $booking))->assertOk()->assertSee('Settled')->assertSee('Refund Receipt PDF');
    }

    public function test_rejection_and_invalid_deductions_leave_funds_untouched(): void
    {
        ['booking' => $booking, 'bank' => $bank] = $this->setupBooking();
        $this->post(route('admin.booking.deposit.request', $booking), ['reason' => 'Missing deduction evidence', 'deductions' => [['description' => 'Damage', 'amount' => 100]]])->assertSessionHasErrors('deductions');
        $refund = $this->request($booking);
        $this->post(route('admin.booking.deposit.request', $booking), ['reason' => 'Duplicate refund request'])->assertSessionHasErrors('deposit');
        $this->post(route('admin.booking.deposit.review', [$booking, $refund]), ['decision' => 'rejected', 'review_notes' => 'Guest is staying'])->assertSessionHasNoErrors();
        $this->post(route('admin.booking.deposit.pay', [$booking, $refund]), $this->payout($bank, 1500))->assertSessionHasErrors('deposit');
        $this->assertSame(1500.0, DepositWallet::totals($booking)['held']);
        $this->assertSame(2500.0, (float) $bank->fresh()->current_balance);
    }

    public function test_carry_forward_moves_hold_without_new_payment_and_blocks_duplicate_charge(): void
    {
        ['booking' => $booking, 'bank' => $bank] = $this->setupBooking();
        $target = $booking->replicate();
        $target->fill(['booking_reference' => 'BK-RENEW', 'invoice_number' => 'INV-RENEW', 'renewed_from_booking_id' => $booking->id, 'security_deposit' => 0]);
        $target->save();
        $invoice = BookingInvoice::create(['booking_id' => $target->id, 'invoice_number' => 'INV-RENEW', 'invoice_type' => 'renewal', 'issue_date' => today(), 'fees' => ['Security Deposit' => 1500], 'total_amount' => 2500]);
        $data = ['target_id' => $target->id, 'amount' => 1500, 'submission_id' => (string) Str::uuid()];
        $this->post(route('admin.booking.deposit.carry', $booking), $data)->assertSessionHasErrors('deposit');
        $invoice->update(['fees' => ['Security Deposit' => 0], 'total_amount' => 1000]);
        $this->post(route('admin.booking.deposit.carry', $booking), $data)->assertSessionHasNoErrors();
        $this->post(route('admin.booking.deposit.carry', $booking), $data)->assertSessionHasNoErrors();
        $this->assertSame(0.0, DepositWallet::totals($booking)['held']);
        $this->assertSame(1500.0, DepositWallet::totals($target)['held']);
        $this->assertSame(2500.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(3, BookingDepositEntry::count());
    }

    public function test_wallet_is_admin_only_and_legacy_paid_status_is_not_a_deposit(): void
    {
        ['booking' => $booking, 'owner' => $owner] = $this->setupBooking(false);
        $this->assertSame(0.0, DepositWallet::totals($booking)['held']);
        $this->post(route('admin.booking.deposit.request', $booking), ['reason' => 'No allocated receipt'])->assertSessionHasErrors('deposit');
        $this->actingAs($owner)->get(route('admin.booking.deposit-wallet', $booking))->assertForbidden();
        $this->post(route('admin.booking.deposit.request', $booking), ['reason' => 'Unauthorised refund'])->assertForbidden();
    }

    public function test_new_deposit_collection_is_atomic_and_idempotent(): void
    {
        ['booking' => $booking, 'bank' => $bank] = $this->setupBooking();
        $invoice = BookingInvoice::create(['booking_id' => $booking->id, 'invoice_number' => 'INV-SECOND-DEPOSIT', 'invoice_type' => 'extension', 'issue_date' => today(), 'fees' => ['Security Deposit' => 100], 'total_amount' => 100, 'status' => 'unpaid']);
        $submission = (string) Str::uuid();
        $data = ['invoice_id' => $invoice->id, 'amount' => 100, 'payment_date' => today()->toDateString(), 'bank_account_id' => $bank->id, 'payment_method' => 'Cash', 'reference' => 'DEP-100', 'submission_id' => $submission];
        $this->post(route('admin.booking.deposit.collect', $booking), $data)->assertSessionHasErrors('receipt');
        $this->post(route('admin.booking.deposit.collect', $booking), [...$data, 'receipt' => UploadedFile::fake()->create('received.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->post(route('admin.booking.deposit.collect', $booking), [...$data, 'receipt' => UploadedFile::fake()->create('received.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(1600.0, DepositWallet::totals($booking)['held']);
        $this->assertSame(2600.0, (float) $bank->fresh()->current_balance);
        $this->delete(route('admin.booking.destroy', $booking))->assertSessionHasErrors('deposit');
        $this->assertNotNull($booking->fresh());
    }

    public function test_insufficient_bank_balance_and_cross_booking_refund_are_blocked(): void
    {
        ['booking' => $booking, 'bank' => $bank] = $this->setupBooking();
        $refund = $this->request($booking);
        $other = $booking->replicate();
        $other->fill(['booking_reference' => 'BK-OTHER', 'invoice_number' => 'INV-OTHER']);
        $other->save();
        $this->post(route('admin.booking.deposit.review', [$other, $refund]), ['decision' => 'approved', 'review_notes' => 'Wrong booking'])->assertNotFound();
        $this->post(route('admin.booking.deposit.review', [$booking, $refund]), ['decision' => 'approved', 'review_notes' => 'Approved refund'])->assertSessionHasNoErrors();
        $emptyBank = BankAccount::create(['name' => 'Empty Bank', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true]);
        $this->post(route('admin.booking.deposit.pay', [$booking, $refund]), $this->payout($emptyBank, 1500))->assertSessionHasErrors('deposit');
        $this->assertSame(1500.0, DepositWallet::totals($booking)['held']);
        $this->assertSame(0.0, $refund->fresh()->paid_amount);
    }
}

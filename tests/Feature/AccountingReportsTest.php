<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\Property;
use App\Models\LandlordAccountEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_categories_group_tables_and_preserve_the_selected_period(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['financial', 'receivables', 'expenses', 'utilities'] as $tab) {
            $response = $this->get(route('admin.accounting.reports', ['report' => $tab, 'date_from' => '2026-08-01', 'date_to' => '2026-08-31']))
                ->assertOk()->assertSee('Accounting Reports')->assertSee('Reporting Period')
                ->assertSee('Expense Register &amp; Downloads', false);
            $document = new \DOMDocument;
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML($response->getContent());
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $xpath = new \DOMXPath($document);
            $this->assertSame(4, $xpath->query('//section[@role="tabpanel"]')->length);
            $this->assertStringContainsString('show active', $document->getElementById('report-panel-'.$tab)->getAttribute('class'));
            $this->assertSame('2026-08-01', $document->getElementById('reportFrom')->getAttribute('value'));
            $this->assertSame(1, $xpath->query('//*[@id="report-panel-receivables"]//*[@id="accounts-receivable"]')->length);
        }
    }

    public function test_profit_and_loss_and_bank_balances_are_calculated_from_posted_entries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $incomeAccount = AccountingAccount::where('code', '4010')->firstOrFail();
        $expenseAccount = AccountingAccount::where('code', '5070')->firstOrFail();
        $bank = BankAccount::create([
            'name' => 'Operating Bank',
            'type' => 'bank',
            'opening_balance' => 1000,
            'current_balance' => 1000,
            'currency' => 'AED',
            'is_active' => true,
        ]);

        AccountingEntry::create([
            'entry_no' => 'JE-INCOME-1',
            'entry_date' => now(),
            'type' => 'income',
            'accounting_account_id' => $incomeAccount->id,
            'paid_from_account_id' => $bank->id,
            'credit' => 500,
            'gross_amount' => 500,
            'approval_status' => 'posted',
        ]);
        AccountingEntry::create([
            'entry_no' => 'JE-EXPENSE-1',
            'entry_date' => now(),
            'type' => 'expense',
            'accounting_account_id' => $expenseAccount->id,
            'paid_from_account_id' => $bank->id,
            'debit' => 200,
            'gross_amount' => 200,
            'approval_status' => 'posted',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.accounting.reports', [
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Profit &amp; Loss', false)
            ->assertSee('AED 300.00');

        $this->actingAs($admin)
            ->get(route('admin.accounting.bank-accounts'))
            ->assertOk()
            ->assertSee('AED 1,300.00');
    }

    public function test_accounts_receivable_identifies_the_guest_booking_and_unit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit AR-1207', 'status' => 'vacant']);
        $booking = Booking::create(['property_id' => $property->id, 'booking_reference' => 'BK-AR-001', 'guest_name' => 'Receivable Guest', 'guest_email' => 'receivable@example.com', 'guest_phone' => '+971500000000', 'guest_passport_id_no' => 'PASS-AR-001', 'check_in' => '2026-08-01', 'check_out' => '2026-08-05', 'rent_amount' => 5000, 'status' => 'confirmed', 'invoice_number' => 'BOOK-INV-AR-001']);
        BookingInvoice::create(['booking_id' => $booking->id, 'invoice_number' => 'INV-AR-001', 'invoice_type' => 'original', 'issue_date' => '2026-08-01', 'total_amount' => 5000, 'status' => 'unpaid']);

        $this->actingAs($admin)->get(route('admin.accounting.reports'))
            ->assertOk()->assertSee('Accounts Receivable - Who Owes')->assertSee('Receivable Guest')->assertSee('BK-AR-001')->assertSee('Unit AR-1207');
        $this->actingAs($admin)->get(route('admin.accounting.dashboard'))
            ->assertOk()->assertSee(route('admin.accounting.reports') . '#accounts-receivable', false);
    }

    public function test_owner_negative_balance_is_included_in_accounts_receivable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord', 'name' => 'Owner Owing Balance']);
        LandlordAccountEntry::create(['landlord_id' => $owner->id, 'entry_date' => '2026-08-01', 'type' => 'owner_loan', 'direction' => 'debit', 'amount' => 20850, 'balance_after' => -20850]);

        $this->actingAs($admin)->get(route('admin.accounting.dashboard'))
            ->assertOk()->assertSee('Accounts Receivable')->assertSee('AED 20,850.00')->assertSee('AED 0.00');
        $this->actingAs($admin)->get(route('admin.accounting.reports'))
            ->assertOk()->assertSee('Owner Owing Balance')->assertSee('Owner / Landlord')->assertSee('AED 20,850.00')
            ->assertSee(route('admin.landlord.account-statement', $owner->id), false);

        $receivableAccount = AccountingAccount::where('code', '1060')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.accounting.chart-of-accounts'))
            ->assertOk()->assertSee('Accounts Receivable')->assertSee('AED 20,850.00')
            ->assertSee(url('/admin/accounting/chart-of-accounts/'.$receivableAccount->id.'/statement'), false);
        $this->actingAs($admin)->get(route('admin.accounting.chart-of-accounts.statement', $receivableAccount))
            ->assertOk()->assertSee('1060 - Accounts Receivable')->assertSee('Owner Owing Balance')
            ->assertSee('Owner Loan / Advance')->assertSee('AED 20,850.00');
    }
}

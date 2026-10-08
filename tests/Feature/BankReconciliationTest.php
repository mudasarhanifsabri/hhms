<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\BankStatementImport;
use App\Models\BankStatementTransaction;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Tests\TestCase;

class BankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $bank): BankAccount
    {
        return BankAccount::create(['name' => $bank.' Operating', 'bank_name' => $bank, 'type' => 'bank',
            'currency' => 'AED', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true]);
    }

    public function test_wio_csv_can_be_confirmed_only_against_matching_recorded_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = $this->account('Wio');
        $entry = AccountingEntry::create(['entry_no' => 'JE-TEST-1', 'entry_date' => '2026-09-15', 'type' => 'income',
            'category' => 'rent', 'description' => 'Rent', 'paid_from_account_id' => $account->id,
            'transaction_reference' => 'WIO-REF-1', 'debit' => 0, 'credit' => 250,
            'approval_status' => 'posted', 'status' => 'posted']);
        $csv = "Date,Reference,Description,Debit,Credit\n15/09/2026,WIO-REF-1,Rent,,250.00\n16/09/2026,UNKNOWN,Other,100.00,\n";
        $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.upload'), [
            'bank' => 'wio', 'bank_account_id' => $account->id,
            'statement' => UploadedFile::fake()->createWithContent('wio.csv', $csv),
        ])->assertRedirect();
        $this->assertDatabaseCount('bank_statement_transactions', 2);
        $this->actingAs($admin)->get(route('admin.accounting.bank-reconciliation'))->assertOk()->assertSee('wio.csv');
        $row = BankStatementTransaction::where('reference', 'WIO-REF-1')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.accounting.bank-reconciliation.show', $row->import))->assertOk()->assertSee('Confirm match');
        $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.confirm', $row), ['accounting_entry_id' => $entry->id])->assertRedirect();
        $this->assertSame('confirmed', $row->fresh()->status);
        $this->assertSame($entry->id, $row->fresh()->accounting_entry_id);
        $this->assertDatabaseCount('accounting_entries', 1);
        $unmatched = BankStatementTransaction::where('reference', 'UNKNOWN')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.confirm', $unmatched), ['accounting_entry_id' => $entry->id])->assertSessionHasErrors('accounting_entry_id');
    }

    public function test_adcb_signed_amount_and_duplicate_import(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = $this->account('ADCB');
        $csv = "Transaction Date;Transaction Reference;Narration;Amount;Type\n2026-09-17;ADCB-1;Supplier;125.50;Debit\n";
        $upload = fn () => ['bank' => 'adcb', 'bank_account_id' => $account->id,
            'statement' => UploadedFile::fake()->createWithContent('adcb.csv', $csv)];
        $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.upload'), $upload())->assertRedirect();
        $this->assertSame(125.5, (float) BankStatementTransaction::firstOrFail()->debit);
        $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.upload'), $upload())->assertSessionHasErrors('statement');
        $this->assertDatabaseCount('bank_statement_imports', 1);
    }

    public function test_adcb_excel_upload_and_one_click_unique_reference_matching(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = $this->account('ADCB');
        $entry = AccountingEntry::create([
            'entry_no' => 'JE-ADCB-1', 'entry_date' => '2026-08-01', 'type' => 'income', 'category' => 'rent',
            'description' => 'August rent', 'paid_from_account_id' => $account->id,
            'transaction_reference' => 'PHUB695492802', 'debit' => 0, 'credit' => 4000,
            'approval_status' => 'posted', 'status' => 'posted',
        ]);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Customer Account Statement'],
            [],
            ['Sr.No', 'Date', 'Value Date', 'Bank Reference No', 'Customer Reference No', 'Description', 'Debit Amount', 'Credit Amount'],
            [1, '01-Aug-2026', '01-Aug-2026', 'PHUB695492802', '.', 'Rent received', '-', '4,000.00'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'adcb-xls-');
        (new Xls($spreadsheet))->save($path);

        $response = $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.upload'), [
            'bank' => 'adcb', 'bank_account_id' => $account->id,
            'statement' => UploadedFile::fake()->createWithContent('Bank Statement.xls', file_get_contents($path)),
        ]);
        @unlink($path);

        $response->assertRedirect();
        $transaction = BankStatementTransaction::firstOrFail();
        $this->assertSame('PHUB695492802', $transaction->reference);
        $this->assertSame(4000.0, (float) $transaction->credit);

        $import = BankStatementImport::firstOrFail();
        $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.confirm-all', $import))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('confirmed', $transaction->fresh()->status);
        $this->assertSame($entry->id, $transaction->fresh()->accounting_entry_id);
    }

    public function test_system_transaction_reference_list_and_csv_export(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = $this->account('ADCB');
        AccountingEntry::create([
            'entry_no' => 'JE-RENT-LIST', 'entry_date' => '2026-10-07', 'type' => 'income', 'category' => 'rent_collection',
            'description' => 'Rent collected for October', 'paid_from_account_id' => $account->id,
            'transaction_reference' => 'PHUB-RENT-123', 'debit' => 0, 'credit' => 7250,
            'approval_status' => 'posted', 'status' => 'posted',
        ]);

        $this->actingAs($admin)->get(route('admin.accounting.bank-reconciliation.system-transactions', ['search' => 'PHUB-RENT-123']))
            ->assertOk()->assertSee('PHUB-RENT-123')->assertSee('Rent collected for October')->assertSee('Not matched');

        $export = $this->actingAs($admin)->get(route('admin.accounting.bank-reconciliation.system-transactions.export', ['search' => 'PHUB-RENT-123']));
        $export->assertOk()->assertDownload();
        $this->assertStringContainsString('PHUB-RENT-123', $export->streamedContent());
        $this->assertStringContainsString('Rent collected for October', $export->streamedContent());
    }

    public function test_full_received_amount_is_used_after_internal_deposit_split(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $account = $this->account('ADCB');
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit 501']);
        $booking = Booking::create([
            'property_id' => $property->id, 'booking_reference' => 'BK-SPLIT-1', 'invoice_number' => 'INV-SPLIT-1',
            'guest_name' => 'Split Guest', 'guest_email' => 'split@example.com', 'guest_phone' => '123',
            'guest_passport_id_no' => 'P123', 'check_in' => '2026-10-01', 'check_out' => '2026-11-01',
            'rent_amount' => 3500, 'security_deposit' => 500, 'total_amount' => 4000,
        ]);
        $invoice = BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-SPLIT-1', 'invoice_type' => 'original',
            'issue_date' => '2026-10-01', 'period_from' => '2026-10-01', 'period_to' => '2026-11-01',
            'rent_amount' => 3500, 'total_amount' => 4000, 'fees' => ['Security Deposit' => 500], 'status' => 'paid',
        ]);
        $receipt = AccountingEntry::create([
            'entry_no' => 'JE-SPLIT-1', 'entry_date' => '2026-10-01', 'type' => 'income', 'category' => 'guest_receipt',
            'description' => 'Guest payment', 'booking_id' => $booking->id, 'property_id' => $property->id,
            'paid_from_account_id' => $account->id, 'transaction_reference' => 'PHUB-FULL-4000',
            'debit' => 0, 'credit' => 3500, 'approval_status' => 'posted', 'status' => 'posted',
        ]);
        BookingInvoicePayment::create([
            'booking_invoice_id' => $invoice->id, 'payment_date' => '2026-10-01', 'amount' => 4000,
            'payment_method' => 'Bank Transfer', 'bank_account_id' => $account->id, 'reference' => 'PHUB-FULL-4000',
            'accounting_entry_id' => $receipt->id, 'created_by' => $admin->id,
        ]);
        AccountingEntry::create([
            'entry_no' => 'DEP-SPLIT-1', 'entry_date' => '2026-10-01', 'type' => 'deposit', 'category' => 'security_deposit',
            'description' => 'Deposit allocation from INV-SPLIT-1', 'booking_id' => $booking->id, 'property_id' => $property->id,
            'paid_from_account_id' => $account->id, 'transaction_reference' => 'internal-payment-id',
            'debit' => 0, 'credit' => 500, 'approval_status' => 'posted', 'status' => 'posted',
        ]);
        $import = \App\Models\BankStatementImport::create([
            'bank_account_id' => $account->id, 'bank' => 'adcb', 'filename' => 'split.xls',
            'file_hash' => hash('sha256', 'split-test'), 'uploaded_by' => $admin->id,
        ]);
        $transaction = $import->transactions()->create([
            'bank_account_id' => $account->id, 'row_number' => 17, 'transaction_date' => '2026-10-01',
            'reference' => 'PHUB-FULL-4000', 'description' => 'Received', 'debit' => 0, 'credit' => 4000,
        ]);

        $page = $this->actingAs($admin)->get(route('admin.accounting.bank-reconciliation.system-transactions', ['search' => 'PHUB-FULL-4000']));
        $page->assertOk()->assertSee('4,000.00')->assertDontSee('3,500.00')->assertDontSee('500.00');

        $this->actingAs($admin)->post(route('admin.accounting.bank-reconciliation.confirm-all', $import))->assertRedirect();
        $this->assertSame('confirmed', $transaction->fresh()->status);
        $this->assertSame($receipt->id, $transaction->fresh()->accounting_entry_id);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseReportDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_filtered_expenses_as_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Expense::create([
            'expense_no' => 'EXP-CLEAN-001', 'expense_date' => '2026-08-01', 'category' => 'cleaning',
            'responsibility' => 'company', 'net_amount' => 100, 'vat_amount' => 5, 'gross_amount' => 105,
            'approval_status' => 'approved', 'description' => 'Cleaning export row',
        ]);
        Expense::create([
            'expense_no' => 'EXP-GAS-002', 'expense_date' => '2026-08-15', 'category' => 'gas',
            'responsibility' => 'owner', 'net_amount' => 200, 'vat_amount' => 10, 'gross_amount' => 210,
            'approval_status' => 'pending', 'description' => 'Excluded export row',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.accounting.expenses.report.csv', [
            'category' => 'cleaning', 'approval_status' => 'approved',
            'date_from' => '2026-08-01', 'date_to' => '2026-08-10',
        ]));

        $response->assertOk()->assertDownload('expense-report-' . now()->format('Y-m-d') . '.csv');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('EXP-CLEAN-001', $csv);
        $this->assertStringNotContainsString('EXP-GAS-002', $csv);
    }

    public function test_admin_can_download_expense_report_as_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.accounting.expenses.report.pdf'))
            ->assertOk()
            ->assertDownload('expense-report-' . now()->format('Y-m-d') . '.pdf');
    }
}

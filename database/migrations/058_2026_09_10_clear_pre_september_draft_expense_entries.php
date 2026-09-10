<?php

use App\Models\AccountingEntry;
use App\Models\Expense;
use App\Models\ExpenseAudit;
use App\Models\LandlordAccountEntry;
use App\Models\UtilityBill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Expense::whereDate('expense_date', '<', '2026-09-01')
            ->where('approval_status', 'draft')
            ->chunkById(100, function ($expenses) {
                foreach ($expenses as $expense) {
                    $affectedLandlords = collect([$expense->landlord_id]);

                    DB::transaction(function () use ($expense, $affectedLandlords) {
                        $ownerEntries = LandlordAccountEntry::where('reference', $expense->expense_no)
                            ->orWhere('reference', 'like', 'DRAFT-REV-'.$expense->expense_no.'-%')
                            ->get();
                        $affectedLandlords->push(...$ownerEntries->pluck('landlord_id')->filter());
                        LandlordAccountEntry::whereIn('id', $ownerEntries->pluck('id'))->delete();

                        AccountingEntry::where('expense_id', $expense->id)->delete();
                        UtilityBill::where('expense_id', $expense->id)->update(['accounting_entry_id' => null]);
                        $expense->update(['accounting_entry_id' => null]);

                        ExpenseAudit::firstOrCreate(
                            ['expense_id' => $expense->id, 'action' => 'pre_september_draft_entries_cleared'],
                            ['reason' => 'All accounting and owner/unit statement entries removed because the expense is Draft.']
                        );
                    });

                    $affectedLandlords->filter()->unique()
                        ->each(fn (string $landlordId) => LandlordAccountEntry::recalculateBalancesFor($landlordId));
                }
            });
    }

    public function down(): void
    {
        // Removed draft postings cannot be reconstructed safely without approving each expense.
    }
};

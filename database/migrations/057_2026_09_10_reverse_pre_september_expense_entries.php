<?php

use App\Models\AccountingEntry;
use App\Models\Expense;
use App\Models\ExpenseAudit;
use App\Models\LandlordAccountEntry;
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
                    DB::transaction(function () use ($expense) {
                        AccountingEntry::where('expense_id', $expense->id)
                            ->where(fn ($query) => $query->whereNull('transaction_reference')
                                ->orWhere('transaction_reference', 'not like', 'DRAFT-REV-%'))
                            ->get()
                            ->each(function (AccountingEntry $source) use ($expense) {
                                $reference = 'DRAFT-REV-'.$expense->id.'-'.$source->id;
                                if (AccountingEntry::where('transaction_reference', $reference)->exists()) {
                                    return;
                                }
                                $reversal = $source->replicate();
                                $reversal->fill([
                                    'entry_no' => 'DR-'.str_replace('-', '', $source->id),
                                    'entry_date' => $source->entry_date,
                                    'description' => 'Draft reset reversal of '.$source->entry_no.' for '.$expense->expense_no,
                                    'debit' => $source->credit,
                                    'credit' => $source->debit,
                                    'transaction_reference' => $reference,
                                    'approval_status' => 'posted',
                                    'status' => 'posted',
                                    'created_by' => null,
                                ]);
                                $reversal->save();
                            });

                        LandlordAccountEntry::where('reference', $expense->expense_no)->get()
                            ->each(function (LandlordAccountEntry $source) use ($expense) {
                                LandlordAccountEntry::firstOrCreate(
                                    ['reference' => 'DRAFT-REV-'.$expense->expense_no.'-'.$source->id],
                                    [
                                        'landlord_id' => $source->landlord_id,
                                        'property_id' => $source->property_id,
                                        'entry_date' => $source->entry_date,
                                        'type' => $source->direction === 'credit' ? 'adjustment_debit' : 'adjustment_credit',
                                        'direction' => $source->direction === 'credit' ? 'debit' : 'credit',
                                        'amount' => $source->amount,
                                        'description' => 'Draft reset reversal of '.$expense->expense_no,
                                    ]
                                );
                            });

                        ExpenseAudit::firstOrCreate(
                            ['expense_id' => $expense->id, 'action' => 'pre_september_draft_reset'],
                            ['reason' => 'Expense set to Draft and all financial entries reversed during September 2026 cleanup.']
                        );
                    });

                    if ($expense->landlord_id) {
                        LandlordAccountEntry::recalculateBalancesFor($expense->landlord_id);
                    }
                }
            });
    }

    public function down(): void
    {
        // Financial cleanup reversals are intentionally retained for audit safety.
    }
};

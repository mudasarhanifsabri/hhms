<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        $vatAccountId = DB::table('accounting_accounts')->where('code', '2040')->value('id');
        if (! $vatAccountId) {
            return;
        }

        DB::table('expenses')->where('sale_vat_amount', '>', 0)
            ->whereIn('approval_status', ['approved', 'paid'])
            ->orderBy('id')->each(function ($expense) use ($vatAccountId) {
                $reference = 'VAT-SALE-'.$expense->expense_no;
                if (DB::table('accounting_entries')->where('expense_id', $expense->id)->where('transaction_reference', $reference)->exists()) {
                    return;
                }
                DB::table('accounting_entries')->insert([
                    'id' => (string) Str::uuid(), 'entry_no' => 'VATEXP-'.Str::upper(Str::random(12)),
                    'entry_date' => $expense->expense_date, 'type' => 'adjustment', 'category' => 'output_vat',
                    'accounting_account_id' => $vatAccountId, 'description' => 'Output VAT payable on tax invoice TI-'.$expense->expense_no,
                    'property_id' => $expense->property_id, 'landlord_id' => $expense->landlord_id, 'booking_id' => $expense->booking_id,
                    'expense_id' => $expense->id, 'debit' => 0, 'credit' => $expense->sale_vat_amount,
                    'vat_rate' => $expense->vat_rate, 'vat_amount' => $expense->sale_vat_amount,
                    'net_amount' => $expense->sale_vat_amount, 'gross_amount' => $expense->sale_vat_amount,
                    'transaction_reference' => $reference, 'status' => 'posted', 'approval_status' => 'posted',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('accounting_entries')->where('transaction_reference', 'like', 'VAT-SALE-%')->delete();
    }
};

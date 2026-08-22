<?php

use App\Models\LandlordAccountEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expenses') || ! Schema::hasTable('landlord_account_entries')) {
            return;
        }

        $affectedLandlords = collect();

        DB::table('expenses')
            ->where('responsibility', 'owner')
            ->whereIn('approval_status', ['approved', 'paid'])
            ->whereNotNull('landlord_id')
            ->orderBy('id')
            ->chunk(100, function ($expenses) use ($affectedLandlords) {
                foreach ($expenses as $expense) {
                    DB::table('expenses')->where('id', $expense->id)->update(['owner_billable' => true]);

                    if (DB::table('landlord_account_entries')->where('reference', $expense->expense_no)->exists()) {
                        continue;
                    }

                    DB::table('landlord_account_entries')->insert([
                        'id' => (string) Str::uuid(),
                        'landlord_id' => $expense->landlord_id,
                        'property_id' => $expense->property_id,
                        'entry_date' => $expense->expense_date,
                        'type' => match ($expense->category) {
                            'dewa', 'gas', 'internet', 'chiller', 'cleaning', 'maintenance' => $expense->category,
                            default => 'other_expense',
                        },
                        'direction' => 'debit',
                        'amount' => $expense->gross_amount,
                        'balance_after' => 0,
                        'reference' => $expense->expense_no,
                        'description' => $expense->description ?: ucfirst($expense->category) . ' expense',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $affectedLandlords->push($expense->landlord_id);
                }
            });

        $affectedLandlords->unique()->each(
            fn (string $landlordId) => LandlordAccountEntry::recalculateBalancesFor($landlordId)
        );
    }

    public function down(): void
    {
        // Financial statement entries are intentionally preserved on rollback.
    }
};

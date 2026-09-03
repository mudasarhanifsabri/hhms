<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandlordAccountEntry extends BaseModel
{
    use HasFactory;

    public const CREDIT_TYPES = [
        'rent_income' => 'Rent Income',
        'loan_repayment' => 'Owner Loan Repayment',
        'adjustment_credit' => 'Credit Adjustment',
    ];

    public const DEBIT_TYPES = [
        'management_fee' => 'Management Fee',
        'dewa' => 'DEWA',
        'gas' => 'Gas',
        'internet' => 'Internet',
        'chiller' => 'Chiller',
        'cleaning' => 'Cleaning',
        'maintenance' => 'Maintenance',
        'furnishing' => 'Furnishing / Setup Cost',
        'owner_loan' => 'Owner Loan / Advance',
        'payout' => 'Owner Payout Transfer',
        'adjustment_debit' => 'Debit Adjustment',
        'other_expense' => 'Other Expense',
    ];

    protected $fillable = [
        'landlord_id',
        'property_id',
        'entry_date',
        'type',
        'direction',
        'amount',
        'balance_after',
        'reference',
        'description',
        'invoice_attachment',
        'receipt_attachment',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::allTypes()[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public static function allTypes(): array
    {
        return self::CREDIT_TYPES + self::DEBIT_TYPES;
    }

    public static function directionForType(string $type): string
    {
        return array_key_exists($type, self::CREDIT_TYPES) ? 'credit' : 'debit';
    }

    public function scopeStatementOrder($query)
    {
        return $query->orderBy('entry_date')->orderBy('reference')
            ->orderByRaw("CASE WHEN direction = 'credit' THEN 0 WHEN type = 'management_fee' THEN 1 ELSE 2 END")
            ->orderBy('created_at')->orderBy('id');
    }

    public static function statementBalancesFor(string $landlordId): array
    {
        $balance = 0;
        $balances = [];
        foreach (self::where('landlord_id', $landlordId)->statementOrder()->get() as $entry) {
            $balance += $entry->direction === 'credit' ? (float) $entry->amount : -(float) $entry->amount;
            $balances[$entry->id] = $balance;
        }

        return $balances;
    }

    public static function recalculateBalancesFor(string $landlordId): void
    {
        $balance = 0;

        self::where('landlord_id', $landlordId)
            ->statementOrder()
            ->get()
            ->each(function (self $entry) use (&$balance) {
                $amount = (float) $entry->amount;
                $balance += $entry->direction === 'credit' ? $amount : -$amount;
                $entry->forceFill(['balance_after' => $balance])->save();
            });
    }
}

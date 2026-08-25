<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends BaseModel
{
    protected $fillable = [
        'accounting_account_id',
        'name',
        'type',
        'bank_name',
        'iban',
        'account_number',
        'currency',
        'opening_balance',
        'current_balance',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function accountingAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class, 'paid_from_account_id');
    }
}

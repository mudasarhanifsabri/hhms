<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingAccount extends BaseModel
{
    public const TYPES = [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'equity' => 'Equity',
        'income' => 'Income',
        'expense' => 'Expense',
    ];

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_code',
        'is_bank_cash',
        'is_system',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_bank_cash' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}

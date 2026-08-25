<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankTransfer extends BaseModel
{
    protected $fillable = [
        'transfer_no', 'transfer_date', 'from_account_id', 'to_account_id',
        'amount', 'currency', 'reference', 'description', 'created_by',
    ];

    protected $casts = ['transfer_date' => 'date', 'amount' => 'decimal:2'];

    public function fromAccount(): BelongsTo { return $this->belongsTo(BankAccount::class, 'from_account_id'); }
    public function toAccount(): BelongsTo { return $this->belongsTo(BankAccount::class, 'to_account_id'); }
    public function entries(): HasMany { return $this->hasMany(AccountingEntry::class); }
}

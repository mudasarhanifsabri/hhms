<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends BaseModel
{
    protected $fillable = [
        'vendor_no',
        'name',
        'category',
        'contact_person',
        'email',
        'phone',
        'trn',
        'address',
        'opening_balance',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}

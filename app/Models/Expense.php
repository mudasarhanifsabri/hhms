<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends BaseModel
{
    public const CATEGORIES = [
        'cleaning' => 'Cleaning',
        'maintenance' => 'Maintenance',
        'dewa' => 'DEWA',
        'gas' => 'Gas',
        'internet' => 'Internet',
        'chiller' => 'Chiller',
        'supplies' => 'Guest Supplies',
        'commission' => 'Commission',
        'license' => 'License / Permit',
        'other' => 'Other',
    ];

    protected $fillable = [
        'expense_no',
        'expense_date',
        'category',
        'supplier',
        'property_id',
        'landlord_id',
        'booking_id',
        'responsibility',
        'owner_billable',
        'net_amount',
        'vat_rate',
        'vat_amount',
        'gross_amount',
        'payment_method',
        'transaction_reference',
        'receipt_path',
        'description',
        'accounting_entry_id',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'owner_billable' => 'boolean',
        'net_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function accountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class);
    }
}

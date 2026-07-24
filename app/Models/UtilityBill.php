<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityBill extends BaseModel
{
    public const STATUSES = [
        'outstanding' => 'Outstanding',
        'paid' => 'Paid',
        'owner_paid' => 'Owner Paid',
        'overdue' => 'Overdue',
    ];

    protected $fillable = [
        'utility_account_id',
        'property_id',
        'landlord_id',
        'booking_id',
        'bill_month',
        'bill_date',
        'due_date',
        'bill_amount',
        'vat_rate',
        'vat_amount',
        'total_amount',
        'responsibility',
        'status',
        'paid_at',
        'paid_by',
        'payment_method',
        'transaction_reference',
        'receipt_path',
        'notes',
        'expense_id',
        'accounting_entry_id',
    ];

    protected $casts = [
        'bill_month' => 'date',
        'bill_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'bill_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(UtilityAccount::class, 'utility_account_id');
    }

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

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function accountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}

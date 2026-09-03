<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingEntry extends BaseModel
{
    public const TYPES = [
        'income' => 'Income',
        'expense' => 'Expense',
        'utility' => 'Utility',
        'vat' => 'VAT',
        'owner' => 'Owner Ledger',
        'adjustment' => 'Adjustment',
        'transfer' => 'Account Transfer',
        'deposit' => 'Guest Deposit',
    ];

    protected $fillable = [
        'entry_no',
        'entry_date',
        'type',
        'category',
        'accounting_account_id',
        'description',
        'property_id',
        'landlord_id',
        'booking_id',
        'paid_from_account_id',
        'vendor_id',
        'expense_id',
        'utility_bill_id',
        'bank_transfer_id',
        'debit',
        'credit',
        'vat_rate',
        'vat_amount',
        'net_amount',
        'gross_amount',
        'payment_method',
        'transaction_reference',
        'attachment',
        'status',
        'approval_status',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
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

    public function accountingAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class);
    }

    public function paidFromAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'paid_from_account_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bankTransfer(): BelongsTo
    {
        return $this->belongsTo(BankTransfer::class);
    }
}

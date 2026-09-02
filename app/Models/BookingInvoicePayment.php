<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingInvoicePayment extends BaseModel
{
    protected $fillable = [
        'booking_invoice_id', 'payment_date', 'amount', 'payment_method',
        'bank_account_id', 'reference', 'receipt_path', 'notes',
        'accounting_entry_id', 'created_by',
    ];

    protected $casts = ['payment_date' => 'date', 'amount' => 'decimal:2'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BookingInvoice::class, 'booking_invoice_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}

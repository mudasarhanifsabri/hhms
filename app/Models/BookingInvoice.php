<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingInvoice extends BaseModel
{
    public const TYPES = [
        'original' => 'Original Booking',
        'extension' => 'Extension',
        'renewal' => 'Renewal',
    ];

    protected $fillable = [
        'booking_id',
        'invoice_number',
        'invoice_type',
        'issue_date',
        'period_from',
        'period_to',
        'rent_amount',
        'vat_rate',
        'vat_amount',
        'fees',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'rent_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'fees' => 'array',
        'total_amount' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->invoice_type] ?? ucfirst($this->invoice_type);
    }
}

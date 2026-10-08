<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialApprovalRequest extends BaseModel
{
    protected $fillable = ['type', 'status', 'booking_id', 'booking_invoice_id', 'booking_invoice_payment_id',
        'payload', 'before_snapshot', 'proof_path', 'request_reason', 'requested_by', 'requested_at',
        'reviewed_by', 'reviewed_at', 'review_notes'];

    protected $casts = ['payload' => 'array', 'before_snapshot' => 'array', 'requested_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(BookingInvoice::class, 'booking_invoice_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(BookingInvoicePayment::class, 'booking_invoice_payment_id'); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'payment_create' => 'New invoice payment', 'combined_payment_create' => 'New combined payment',
            'payment_edit' => 'Edit payment details', 'payment_reverse' => 'Delete wrong payment',
            'invoice_edit' => 'Edit invoice amounts',
            default => str($this->type)->replace('_', ' ')->headline(),
        };
    }
}

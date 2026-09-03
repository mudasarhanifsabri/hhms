<?php

namespace App\Models;

class BookingDepositRefund extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = ['held_at_request' => 'decimal:2', 'deduction_amount' => 'decimal:2', 'refund_amount' => 'decimal:2', 'deductions' => 'array', 'reviewed_at' => 'datetime'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function entries()
    {
        return $this->hasMany(BookingDepositEntry::class, 'refund_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function inspection()
    {
        return $this->belongsTo(BookingInspection::class, 'inspection_id');
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->entries()->where('kind', 'refunded')->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, round((float) $this->refund_amount - $this->paid_amount, 2));
    }
}

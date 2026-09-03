<?php

namespace App\Models;

class BookingDepositEntry extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = ['amount' => 'decimal:2', 'entry_date' => 'date'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function refund()
    {
        return $this->belongsTo(BookingDepositRefund::class, 'refund_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function relatedBooking()
    {
        return $this->belongsTo(Booking::class, 'related_booking_id');
    }
}

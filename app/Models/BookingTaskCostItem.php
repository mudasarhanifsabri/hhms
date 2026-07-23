<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingTaskCostItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'booking_task_id',
        'type',
        'label',
        'worker',
        'hours',
        'rate',
        'quantity',
        'unit_price',
        'amount',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(BookingTask::class, 'booking_task_id');
    }
}

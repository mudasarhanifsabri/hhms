<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingTaskActivity extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'booking_task_id',
        'user_id',
        'action',
        'comment',
        'gps_latitude',
        'gps_longitude',
        'meta',
    ];

    protected $casts = [
        'gps_latitude' => 'decimal:7',
        'gps_longitude' => 'decimal:7',
        'meta' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(BookingTask::class, 'booking_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

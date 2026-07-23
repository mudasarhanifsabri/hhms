<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingTaskRemark extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'booking_task_id',
        'user_id',
        'remark',
        'status_update',
        'pictures',
    ];

    protected $casts = [
        'pictures' => 'array',
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

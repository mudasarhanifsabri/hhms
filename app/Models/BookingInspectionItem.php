<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingInspectionItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'booking_inspection_id',
        'area',
        'item',
        'condition',
        'comment',
        'pictures',
        'sort_order',
    ];

    protected $casts = [
        'pictures' => 'array',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(BookingInspection::class, 'booking_inspection_id');
    }
}

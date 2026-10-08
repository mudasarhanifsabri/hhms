<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingHistory extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'title',
        'description',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** Human-readable audit text; database UUIDs remain unchanged internally. */
    public function getDisplayDescriptionAttribute(): string
    {
        $description = (string) $this->description;
        if (! str_contains((string)$this->title, 'Payment') && ! str_contains((string)$this->title, 'Approval') && ! str_contains((string)$this->title, 'Financial Request')) {
            return $description;
        }
        $index = 0;
        $approvalFirst = str_contains((string)$this->title, 'Approval') || str_contains((string)$this->title, 'Financial Request');
        $date = $this->created_at?->format('Ymd') ?? now()->format('Ymd');
        return preg_replace_callback('/\b[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\b/i', function ($match) use (&$index, $approvalFirst, $date) {
            $prefix = $approvalFirst && $index++ === 0 ? 'APR' : 'TRX';
            return $prefix.'-'.$date.'-'.strtoupper(substr(str_replace('-', '', $match[0]), 0, 8));
        }, $description) ?? $description;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingInspection extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'property_id',
        'submitted_by',
        'inspection_number',
        'type',
        'status',
        'selected_areas',
        'total_items',
        'good_items',
        'issue_items',
        'na_items',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'selected_areas' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingInspectionItem::class)->orderBy('sort_order');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'check_out' ? 'Check Out' : 'Check In';
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusClassAttribute(): string
    {
        return $this->status === 'submitted' ? 'bg-success' : 'bg-warning';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class BookingInspection extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'property_id',
        'booking_task_id',
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

    public function task(): BelongsTo
    {
        return $this->belongsTo(BookingTask::class, 'booking_task_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingInspectionItem::class)->orderBy('sort_order');
    }

    public function recalculateSummary(): void
    {
        $counts = $this->items()
            ->select('condition', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('condition')
            ->pluck('aggregate', 'condition');

        $summary = [
            'total_items' => (int) $counts->sum(),
            'good_items' => (int) ($counts['good'] ?? 0),
            'issue_items' => (int) ($counts['issue'] ?? 0),
            'na_items' => (int) ($counts['na'] ?? 0),
        ];

        if (collect($summary)->contains(fn ($value, $key) => (int) $this->getAttribute($key) !== $value)) {
            $this->forceFill($summary)->save();
        }

        $this->setRawAttributes(array_merge($this->getAttributes(), $summary), true);
        $this->unsetRelation('items');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'check_out' => 'Check Out',
            'routine' => 'Routine',
            'maintenance' => 'Maintenance',
            'cleaning' => 'Cleaning',
            default => 'Check In',
        };
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

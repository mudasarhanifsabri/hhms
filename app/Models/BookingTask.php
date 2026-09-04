<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingTask extends BaseModel
{
    use HasFactory;

    public const TYPES = [
        'cleaning' => 'Cleaning',
        'maintenance' => 'Maintenance',
        'inspection' => 'Inspection',
        'checkout_inspection' => 'Check Out Inspection',
        'other' => 'Other',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    protected $fillable = [
        'task_number',
        'booking_id',
        'property_id',
        'assigned_to',
        'created_by',
        'type',
        'category',
        'priority',
        'due_date',
        'title',
        'status',
        'progress',
        'description',
        'pictures',
        'invoice_attachment',
        'receipt_attachment',
        'warranty_attachment',
        'labor_cost',
        'material_cost',
        'other_expenses',
        'total_cost',
        'accepted_at',
        'started_at',
        'expected_completion_date',
        'completed_at',
        'completion_notes',
        'final_images',
    ];

    protected $casts = [
        'due_date' => 'date',
        'pictures' => 'array',
        'final_images' => 'array',
        'labor_cost' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'other_expenses' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'expected_completion_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function remarks(): HasMany
    {
        return $this->hasMany(BookingTaskRemark::class)->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(BookingTaskActivity::class)->latest();
    }

    public function costItems(): HasMany
    {
        return $this->hasMany(BookingTaskCostItem::class);
    }

    public function inspection(): HasOne
    {
        return $this->hasOne(BookingInspection::class, 'booking_task_id');
    }

    public function expenseRequests(): HasMany
    {
        return $this->hasMany(Expense::class, 'booking_task_id');
    }

    public function isInspectionTask(): bool
    {
        return in_array($this->type, ['inspection', 'checkout_inspection'], true);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->is_overdue) {
            return 'Overdue';
        }

        return match ($this->status) {
            'new', 'open' => 'Pending',
            'assigned' => 'Assigned',
            'waiting_approval' => 'Waiting Approval',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusClassAttribute(): string
    {
        if ($this->is_overdue) {
            return 'bg-danger';
        }

        return match ($this->status) {
            'accepted' => 'bg-info',
            'assigned' => 'bg-primary',
            'in_progress' => 'bg-primary',
            'waiting_approval' => 'bg-secondary',
            'completed' => 'bg-success',
            'closed' => 'bg-dark',
            'cancelled' => 'bg-danger',
            default => 'bg-warning',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst((string) $this->priority);
    }

    public function getTaskDisplayNumberAttribute(): string
    {
        return $this->task_number ?: 'TSK-' . substr((string) $this->id, 0, 8);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && ! in_array($this->status, ['completed', 'closed', 'cancelled'], true);
    }

    public function recalculateCosts(): void
    {
        $labor = (float) $this->costItems()->where('type', 'labor')->sum('amount');
        $materials = (float) $this->costItems()->where('type', 'material')->sum('amount');
        $other = (float) $this->costItems()->where('type', 'other')->sum('amount');

        $this->update([
            'labor_cost' => $labor,
            'material_cost' => $materials,
            'other_expenses' => $other,
            'total_cost' => $labor + $materials + $other,
        ]);
    }
}

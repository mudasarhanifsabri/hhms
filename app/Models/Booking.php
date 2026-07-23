<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'booking_reference',
        'property_id',
        'agent_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_passport_id_no',
        'guest_document',
        'check_in',
        'check_in_time',
        'check_out',
        'check_out_time',
        'rent_amount',
        'management_fee_percent',
        'management_fee_amount',
        'owner_rent_income',
        'vat_included',
        'vat_amount',
        'dtcm_fee',
        'cleaning_fee',
        'agency_fee',
        'security_deposit',
        'total_amount',
        'status',
        'checked_in_at',
        'checked_out_at',
        'invoice_number',
        'invoice_status',
        'payment_proof',
        'notes',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'rent_amount' => 'decimal:2',
        'management_fee_percent' => 'decimal:2',
        'management_fee_amount' => 'decimal:2',
        'owner_rent_income' => 'decimal:2',
        'vat_included' => 'boolean',
        'vat_amount' => 'decimal:2',
        'dtcm_fee' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'agency_fee' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(BookingHistory::class)->latest();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(BookingTask::class)->latest();
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(BookingInspection::class)->latest();
    }

    public function getWorkflowStatusLabelAttribute(): string
    {
        if ($this->invoice_status !== 'paid') {
            return 'Unpaid';
        }

        return match ($this->status) {
            'checked_in' => 'Checked In',
            'checked_out' => 'Checked Out',
            default => 'Paid & Confirmed',
        };
    }

    public function getWorkflowStatusClassAttribute(): string
    {
        if ($this->invoice_status !== 'paid') {
            return 'bg-warning';
        }

        return match ($this->status) {
            'checked_in' => 'bg-info',
            'checked_out' => 'bg-dark',
            default => 'bg-success',
        };
    }

    public function getNightsAttribute(): int
    {
        if (! $this->check_in || ! $this->check_out) {
            return 0;
        }

        return max(1, $this->check_in->diffInDays($this->check_out));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitManagementCancellation extends BaseModel
{
    protected $fillable = [
        'property_id', 'owner_id', 'reference_no', 'letter_date', 'effective_date',
        'property_description', 'owner_name', 'owner_email', 'company_signer_name',
        'company_signer_email', 'status', 'owner_token', 'company_token',
        'owner_signature', 'owner_signed_name', 'owner_sent_at', 'owner_viewed_at',
        'owner_signed_at', 'company_signature', 'company_signed_name', 'company_sent_at',
        'company_viewed_at', 'company_signed_at', 'completed_at', 'expires_at',
        'final_document_path', 'document_hash', 'cancelled_at', 'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'letter_date' => 'date', 'effective_date' => 'date', 'expires_at' => 'date',
        'owner_sent_at' => 'datetime', 'owner_viewed_at' => 'datetime',
        'owner_signed_at' => 'datetime', 'company_sent_at' => 'datetime',
        'company_viewed_at' => 'datetime', 'company_signed_at' => 'datetime',
        'completed_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }
    public function events(): HasMany { return $this->hasMany(UnitManagementCancellationEvent::class, 'cancellation_id')->oldest(); }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->headline()->toString();
    }
}

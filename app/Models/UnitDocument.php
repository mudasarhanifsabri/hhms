<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitDocument extends BaseModel
{
    public const TYPES = [
        'noc' => 'NOC',
        'management_contract' => 'Management Contract',
        'management_letter' => 'Management Letter',
        'dtcm_permit' => 'DTCM Permit',
        'title_deed' => 'Title Deed',
        'insurance' => 'Insurance',
        'custom' => 'Custom Document',
    ];

    protected $fillable = [
        'property_id', 'owner_id', 'type', 'custom_title', 'reference_no',
        'issue_date', 'expires_at', 'file_path', 'notes', 'source',
    ];

    protected $casts = ['issue_date' => 'date', 'expires_at' => 'date'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getTitleAttribute(): string
    {
        return $this->type === 'custom'
            ? ($this->custom_title ?: 'Custom Document')
            : (self::TYPES[$this->type] ?? (string) str($this->type)->headline());
    }

    public function getExpiryStatusAttribute(): string
    {
        if (! $this->expires_at) return 'no_expiry';
        if ($this->expires_at->isPast()) return 'expired';
        if ($this->expires_at->lte(now()->addDays(30))) return 'expiring';

        return 'valid';
    }
}

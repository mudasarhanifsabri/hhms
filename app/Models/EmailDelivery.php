<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDelivery extends BaseModel
{
    protected $fillable = ['type', 'status', 'recipients', 'subject', 'purpose', 'landlord_id', 'property_id', 'created_by', 'queued_at', 'sent_at', 'failed_at', 'failure_reason'];
    protected $casts = ['recipients' => 'array', 'queued_at' => 'datetime', 'sent_at' => 'datetime', 'failed_at' => 'datetime'];
    public function landlord(): BelongsTo { return $this->belongsTo(User::class, 'landlord_id')->withTrashed(); }
    public function property(): BelongsTo { return $this->belongsTo(Property::class)->withTrashed(); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
}

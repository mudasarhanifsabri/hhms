<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitManagementCancellationEvent extends BaseModel
{
    protected $fillable = ['cancellation_id', 'actor_role', 'event', 'ip_address', 'user_agent', 'metadata'];
    protected $casts = ['metadata' => 'array'];
    public function cancellation(): BelongsTo { return $this->belongsTo(UnitManagementCancellation::class, 'cancellation_id'); }
}

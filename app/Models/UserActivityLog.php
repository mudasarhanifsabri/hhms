<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends BaseModel
{
    protected $fillable = ['user_id', 'user_email', 'event', 'method', 'route_name', 'url_path', 'ip_address',
        'forwarded_for', 'user_agent', 'device', 'status_code', 'duration_ms', 'description', 'metadata', 'occurred_at'];
    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class)->withTrashed(); }
}

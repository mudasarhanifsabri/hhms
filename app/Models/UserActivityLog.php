<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends BaseModel
{
    protected $fillable = ['user_id', 'user_email', 'event', 'method', 'route_name', 'url_path', 'ip_address',
        'forwarded_for', 'user_agent', 'device', 'location_city', 'location_region', 'location_country',
        'location_country_code', 'location_latitude', 'location_longitude', 'location_timezone', 'location_resolved_at',
        'status_code', 'duration_ms', 'description', 'metadata', 'occurred_at'];
    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime', 'location_resolved_at' => 'datetime',
        'location_latitude' => 'decimal:7', 'location_longitude' => 'decimal:7'];
    public function user(): BelongsTo { return $this->belongsTo(User::class)->withTrashed(); }
}

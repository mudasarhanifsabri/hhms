<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyOwnerShare extends Model
{
    protected $fillable = [
        'property_id',
        'owner_id',
        'share_percent',
        'is_primary',
    ];

    protected $casts = [
        'share_percent' => 'decimal:2',
        'is_primary' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}

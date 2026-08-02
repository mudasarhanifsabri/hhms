<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappWebhookEvent extends Model
{
    protected $fillable = [
        'event_type',
        'message_id',
        'from_number',
        'to_number',
        'status',
        'status_at',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'status_at' => 'datetime',
    ];
}

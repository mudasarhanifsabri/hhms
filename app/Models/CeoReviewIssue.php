<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CeoReviewIssue extends BaseModel
{
    protected $fillable = [
        'week_ending', 'issue', 'impact', 'recommendation', 'owner_name',
        'due_date', 'decision', 'status', 'created_by', 'decided_by', 'decided_at',
    ];

    protected $casts = [
        'week_ending' => 'date',
        'due_date' => 'date',
        'decided_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}

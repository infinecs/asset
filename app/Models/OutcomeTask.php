<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutcomeTask extends Model
{
    protected $fillable = [
        'user_id', 'start_date', 'end_date', 'category', 'department',
        'job_description', 'man_hour', 'is_completed',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'man_hour' => 'decimal:2',
        'is_completed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

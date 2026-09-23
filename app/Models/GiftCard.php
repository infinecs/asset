<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCard extends Model
{
    protected $fillable = [
        'employee_id', 'occasion_year', 'birthday_date', 'gift_card_code',
        'status', 'reminder_count', 'last_reminded_at', 'sent_at',
        'manager_notified_3d_at', 'manager_notified_2d_at',
    ];

    protected $casts = [
        'birthday_date' => 'date',
        'last_reminded_at' => 'datetime',
        'sent_at' => 'datetime',
        'manager_notified_3d_at' => 'datetime',
        'manager_notified_2d_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'success',
            'ready' => 'primary',
            'reminding' => 'warning',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'Sent',
            'ready' => 'Ready to Send',
            'reminding' => 'Reminder Sent',
            default => 'Pending',
        };
    }
}

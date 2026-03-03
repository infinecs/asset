<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestTicket extends Model
{
    protected $fillable = [
        'ticket_number', 'requested_by', 'asset_id', 'category_id',
        'type', 'subject', 'description', 'priority', 'status',
        'assigned_to', 'resolution_notes', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'open' => 'warning',
            'in_progress' => 'primary',
            'resolved' => 'success',
            'closed' => 'secondary',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function getPriorityBadgeAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'secondary',
            'medium' => 'info',
            'high' => 'warning',
            'critical' => 'danger',
            default => 'secondary',
        };
    }

    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $year = now()->format('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return $prefix . '-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}

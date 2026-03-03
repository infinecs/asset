<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lease extends Model
{
    protected $fillable = [
        'lease_number',
        'asset_id',
        'lessee_id',
        'issued_by',
        'lease_start',
        'lease_end',
        'terms',
        'status',
        'signed_at',
        'returned_at',
        'returned_by',
        'returned_notes',
        'signed_name',
        'signed_ip',
        'signed_user_agent',
    ];

    protected $casts = [
        'lease_start' => 'date',
        'lease_end' => 'date',
        'signed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function lessee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lessee_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'signed' => 'success',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    protected $fillable = [
        'asset_tag', 'name', 'brand', 'model', 'serial_number',
        'brand_id', 'category_id', 'location_id', 'assigned_to', 'status',
        'purchase_date', 'purchase_cost', 'warranty_expiry',
        'notes', 'last_seen_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'last_seen_at' => 'datetime',
        'purchase_cost' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AssetHistory::class)->latest();
    }

    public function requestTickets(): HasMany
    {
        return $this->hasMany(RequestTicket::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class)->latest();
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'available' => 'success',
            'in_use' => 'primary',
            'under_maintenance' => 'warning',
            'retired' => 'secondary',
            'lost' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'available' => 'Available',
            'in_use' => 'In Use',
            'under_maintenance' => 'Under Maintenance',
            'retired' => 'Retired',
            'lost' => 'Lost',
            default => 'Unknown',
        };
    }

    public function getBrandLabelAttribute(): string
    {
        return $this->brand?->name ?? $this->brand ?? '-';
    }

    public function isWarrantyExpiringSoon(): bool
    {
        if (!$this->warranty_expiry) {
            return false;
        }
        return $this->warranty_expiry->diffInDays(now()) <= 30 && $this->warranty_expiry->isFuture();
    }

    public function isWarrantyExpired(): bool
    {
        if (!$this->warranty_expiry) {
            return false;
        }
        return $this->warranty_expiry->isPast();
    }
}

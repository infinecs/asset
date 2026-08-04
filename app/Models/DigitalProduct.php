<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DigitalProduct extends Model
{
    protected $fillable = [
        'name', 'brand_id', 'plan', 'purchase_date', 'purchase_cost', 'renewal_date', 'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'renewal_date' => 'date',
        'purchase_cost' => 'decimal:2',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'digital_product_employee')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    public function isRenewalExpired(): bool
    {
        return $this->renewal_date && $this->renewal_date->isPast();
    }

    public function isRenewalExpiringSoon(): bool
    {
        return $this->renewal_date && !$this->isRenewalExpired() && $this->renewal_date->diffInDays(now()) <= 30;
    }
}

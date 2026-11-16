<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Asset extends Model
{
    /**
     * Asset types that stay on a desk rather than travelling with a person. Only these can be
     * placed at a workstation.
     */
    public const FIXED_TYPES = ['desktop', 'monitor', 'speakerphone'];

    protected $fillable = [
        'type', 'asset_tag', 'name', 'brand', 'model', 'serial_number', 'service_tag',
        'brand_id', 'category_id', 'location_id', 'workstation_id', 'assigned_to', 'assigned_to_other', 'status',
        'purchase_date', 'purchase_cost', 'warranty_expiry',
        'notes', 'photo_path', 'last_seen_at',
        'cpu', 'ram', 'storage', 'display',
        'agreement_token', 'agreement_sent_at', 'agreement_signed_at',
        'agreement_signature_path', 'agreement_signed_name',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'last_seen_at' => 'datetime',
        'purchase_cost' => 'decimal:2',
        'agreement_sent_at' => 'datetime',
        'agreement_signed_at' => 'datetime',
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

    public function workstation(): BelongsTo
    {
        return $this->belongsTo(Workstation::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AssetHistory::class)->latest();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class)->latest()->latest('id');
    }

    public static function isFixedType(?string $type): bool
    {
        return in_array($type, self::FIXED_TYPES, true);
    }

    public function isFixed(): bool
    {
        return self::isFixedType($this->type);
    }

    /**
     * Move this asset to another workstation (or off any workstation when $to is null). The asset
     * takes the workstation's location, and its status follows the placement: placing an available
     * (or lost) asset marks it in use, and taking an otherwise unassigned asset off its desk frees it.
     *
     * Pass $syncStatus = false to leave the status alone, e.g. when an audit finds the asset
     * missing from its desk: that doesn't make it available.
     *
     * Returns false when the asset is already there.
     */
    public function moveToWorkstation(?Workstation $to, ?string $notes = null, bool $syncStatus = true): bool
    {
        if ((int) $this->workstation_id === (int) $to?->id) {
            return false;
        }

        $from = $this->workstation;
        $attributes = ['workstation_id' => $to?->id];

        if ($to) {
            $attributes['location_id'] = $to->location_id;
            // A lost asset turning up at a desk has been found.
            if ($syncStatus && in_array($this->status, ['available', 'lost'], true)) {
                $attributes['status'] = 'in_use';
            }
        } elseif ($syncStatus && $this->status === 'in_use' && !$this->assigned_to && !$this->assigned_to_other) {
            $attributes['status'] = 'available';
        }

        $changes = [];
        foreach (['location_id', 'status'] as $field) {
            if (array_key_exists($field, $attributes) && $this->$field != $attributes[$field]) {
                $changes[$field] = ['old' => $this->$field, 'new' => $attributes[$field]];
            }
        }

        DB::transaction(function () use ($attributes, $from, $to, $notes, $changes) {
            $this->update($attributes);
            $this->unsetRelation('workstation');
            $this->recordWorkstationMove($from, $to, $notes, $changes);
        });

        return true;
    }

    /**
     * Log a workstation change that has already been saved: one row in the movement log plus an
     * entry in the asset's history timeline. $extraChanges are other fields changed by the move.
     */
    public function recordWorkstationMove(?Workstation $from, ?Workstation $to, ?string $notes = null, array $extraChanges = []): AssetMovement
    {
        $movement = AssetMovement::create([
            'asset_id' => $this->id,
            'from_workstation_id' => $from?->id,
            'to_workstation_id' => $to?->id,
            'from_label' => $from?->label,
            'to_label' => $to?->label,
            'user_id' => auth()->id(),
            'notes' => $notes,
        ]);

        $summary = match (true) {
            $from && $to => "Moved from {$from->label} to {$to->label}",
            (bool) $to => "Placed at {$to->label}",
            default => "Removed from {$from?->label}",
        };

        AssetHistory::create([
            'asset_id' => $this->id,
            'user_id' => auth()->id(),
            'action' => 'moved',
            'notes' => $notes ? "{$summary}: {$notes}" : $summary,
            'changes' => ['workstation' => ['old' => $from?->label, 'new' => $to?->label]] + $extraChanges,
        ]);

        return $movement;
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

    /**
     * Name of whoever holds the asset: the assigned employee, or the free-text
     * non-employee assignee (e.g. an office PC), or null when unassigned.
     */
    public function getAssigneeNameAttribute(): ?string
    {
        return $this->assignedEmployee?->name ?? $this->assigned_to_other;
    }

    /**
     * Bootstrap Icons name for the asset type.
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'desktop' => 'pc-display',
            'monitor' => 'display',
            'smartphone' => 'phone',
            'tablet' => 'tablet',
            'speakerphone' => 'speaker',
            default => 'laptop',
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

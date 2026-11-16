<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A desk inside an office (Location). Fixed assets such as monitors are bound to a workstation so
 * that moving one from desk to desk is tracked (see AssetMovement).
 *
 * Its name is a generated code following the office labels, e.g. INFP1ARAWS01:
 * "INF" + location code (P1A = site P1, block A) + room (RA) + "WS" + two-digit number (01).
 */
class Workstation extends Model
{
    public const COMPANY_PREFIX = 'INF';
    public const MARKER = 'WS';

    /**
     * A desk is due for an audit when it hasn't been checked for this many days.
     */
    public const AUDIT_INTERVAL_DAYS = 90;

    protected $fillable = ['location_id', 'room', 'number', 'occupant_id', 'notes'];

    protected $casts = [
        'number' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Workstation $workstation) {
            $workstation->room = $workstation->room !== null && $workstation->room !== '' ? strtoupper($workstation->room) : null;
            $workstation->name = static::buildCode(
                Location::whereKey($workstation->location_id)->value('code'),
                $workstation->room,
                (int) $workstation->number,
            );
        });
    }

    public static function buildCode(?string $locationCode, ?string $room, int $number): string
    {
        return self::COMPANY_PREFIX
            . strtoupper((string) $locationCode)
            . strtoupper((string) $room)
            . self::MARKER
            . str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function occupant(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'occupant_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(WorkstationAudit::class)->latest()->latest('id');
    }

    public function latestAudit(): HasOne
    {
        return $this->hasOne(WorkstationAudit::class)->latestOfMany();
    }

    /**
     * Never audited, or last audited longer ago than AUDIT_INTERVAL_DAYS. Needs latestAudit loaded.
     */
    public function isAuditDue(): bool
    {
        $last = $this->latestAudit?->created_at;

        return !$last || $last->lt(now()->subDays(self::AUDIT_INTERVAL_DAYS));
    }

    /**
     * The desk's own part of the code (WS01), for compact views where the room is already shown.
     */
    public function getDeskCodeAttribute(): string
    {
        return self::MARKER . str_pad((string) $this->number, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Movements into or out of this workstation, newest first.
     */
    public function movements(): Builder
    {
        return AssetMovement::query()
            ->where(fn ($q) => $q->where('from_workstation_id', $this->id)->orWhere('to_workstation_id', $this->id))
            ->latest()
            ->latest('id');
    }

    /**
     * How the workstation is shown in messages and the movement log. The code already names the
     * site, block and room (INFP1ARAWS01), so it's used as-is to match the physical labels.
     */
    public function getLabelAttribute(): string
    {
        return $this->name;
    }
}

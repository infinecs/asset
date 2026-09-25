<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'name', 'id_number', 'work_location', 'email', 'status', 'date_of_birth', 'gift_card_opt_out',
        'role_id', 'team_id', 'manager_id', 'is_manager', 'join_date', 'resigned_date',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'join_date' => 'date',
        'resigned_date' => 'date',
        'gift_card_opt_out' => 'boolean',
        'is_manager' => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Teams this employee manages. An employee can be the manager of several teams.
     */
    public function managedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'manager_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Whether this employee has anyone under them on the org chart: an active direct report, or
     * a top-level role (which also collects everyone who has no manager).
     */
    public function hasReportingLine(): bool
    {
        $reports = $this->relationLoaded('subordinates') ? $this->subordinates : $this->subordinates()->get();

        return $reports->contains('status', 'active') || (bool) $this->role?->is_top_level;
    }

    /**
     * Whether making $managerId this employee's manager would create a reporting loop
     * (i.e. $managerId is this employee itself, or already sits somewhere below it in the tree).
     */
    public function wouldCreateCycle(?int $managerId): bool
    {
        if (!$managerId) {
            return false;
        }

        if ($managerId === $this->id) {
            return true;
        }

        $visited = [];
        $current = Employee::find($managerId);

        while ($current && $current->manager_id) {
            if (in_array($current->manager_id, $visited, true)) {
                break;
            }

            if ($current->manager_id === $this->id) {
                return true;
            }

            $visited[] = $current->manager_id;
            $current = $current->manager()->first();
        }

        return false;
    }

    /**
     * Employees who were on staff at any point between $start and $end: joined on or before $end
     * (or join date unknown) and not resigned before $start. A resigned employee with no
     * resignation date is treated as gone for every period.
     */
    public function scopeEmployedDuring(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query
            ->where(fn ($q) => $q->whereNull('join_date')->orWhereDate('join_date', '<=', $end))
            ->where(fn ($q) => $q->where(fn ($active) => $active->where('status', '!=', 'resigned')->whereNull('resigned_date'))
                ->orWhereDate('resigned_date', '>=', $start));
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'assigned_to');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function digitalProducts(): BelongsToMany
    {
        return $this->belongsToMany(DigitalProduct::class, 'digital_product_employee')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(EmployeeHistory::class);
    }

    /**
     * The next upcoming occurrence of this employee's birthday (today or later).
     */
    public function nextBirthdayOccurrence(): ?Carbon
    {
        if (!$this->date_of_birth) {
            return null;
        }

        $today = Carbon::today();
        $occurrence = $this->date_of_birth->copy()->year($today->year)->startOfDay();

        if ($occurrence->lt($today)) {
            $occurrence = $occurrence->addYear();
        }

        return $occurrence;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'resigned' => 'secondary',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'resigned' => 'Resigned',
            default => 'Unknown',
        };
    }
}

<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Employee extends Model
{
    protected $fillable = [
        'name', 'id_number', 'work_location', 'email', 'status', 'date_of_birth', 'gift_card_opt_out',
        'role_id', 'team_id', 'manager_id', 'is_manager', 'join_date', 'resigned_date', 'onboarded_at',
    ];

    /**
     * Record checks: what's missing or out of date on an employee's record, key => short label.
     * The first group applies to active staff, the second to people who have left.
     * scopeWithRecordIssue() finds them in the database and recordIssues() explains them.
     */
    public const RECORD_CHECKS = [
        'no_manager' => 'No manager',
        'no_team' => 'No team',
        'no_role' => 'No role',
        'no_join_date' => 'No join date',
        'no_birthday' => 'No date of birth',
        'manager_left' => 'Reports to someone who has left',
        'left_with_assets' => 'Left, still holds assets',
        'left_with_licences' => 'Left, still has licences',
        'left_still_seated' => 'Left, still seated at a desk',
        'left_with_reports' => 'Left, people still report to them',
        'no_resigned_date' => 'Left, no resignation date',
    ];

    /**
     * Someone who joined within this many days (and hasn't been marked onboarded) gets the
     * onboarding checklist.
     */
    public const ONBOARDING_WINDOW_DAYS = 60;

    protected $casts = [
        'date_of_birth' => 'date',
        'join_date' => 'date',
        'resigned_date' => 'date',
        'onboarded_at' => 'datetime',
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

    /**
     * Desks this employee sits at (usually one).
     */
    public function workstations(): HasMany
    {
        return $this->hasMany(Workstation::class, 'occupant_id');
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
     * Employees with the given RECORD_CHECKS problem.
     */
    public function scopeWithRecordIssue(Builder $query, string $check): void
    {
        $active = fn () => $query->where('status', 'active');
        $left = fn () => $query->where('status', 'resigned');

        match ($check) {
            'no_manager' => $active()->whereNull('manager_id')
                ->whereDoesntHave('role', fn (Builder $r) => $r->where('is_top_level', true)),
            'no_team' => $active()->where('is_manager', false)->whereNull('team_id'),
            'no_role' => $active()->whereNull('role_id'),
            'no_join_date' => $active()->whereNull('join_date'),
            'no_birthday' => $active()->whereNull('date_of_birth')->where('gift_card_opt_out', false),
            'manager_left' => $active()->whereHas('manager', fn (Builder $m) => $m->where('status', '!=', 'active')),
            'left_with_assets' => $left()->has('assets'),
            'left_with_licences' => $left()->has('digitalProducts'),
            'left_still_seated' => $left()->has('workstations'),
            'left_with_reports' => $left()->whereHas('subordinates', fn (Builder $s) => $s->where('status', 'active')),
            'no_resigned_date' => $left()->whereNull('resigned_date'),
            default => throw new \InvalidArgumentException("Unknown record check [{$check}]."),
        };
    }

    /**
     * Employees with at least one RECORD_CHECKS problem.
     */
    public function scopeNeedsAttention(Builder $query): void
    {
        $query->where(function (Builder $any) {
            foreach (array_keys(self::RECORD_CHECKS) as $check) {
                $any->orWhere(fn (Builder $q) => $q->withRecordIssue($check));
            }
        });
    }

    /**
     * What's wrong with this record, as check key => message. Uses *_count attributes from
     * withRecordCounts() when present, else loaded relations, else queries.
     *
     * @return array<string, string>
     */
    public function recordIssues(): array
    {
        $issues = [];

        if ($this->status === 'active') {
            if (!$this->manager_id && !$this->role?->is_top_level) {
                $issues['no_manager'] = 'No manager set. The org chart puts them under the top of the company.';
            }
            if (!$this->is_manager && !$this->team_id) {
                $issues['no_team'] = 'Not in a team.';
            }
            if (!$this->role_id) {
                $issues['no_role'] = 'No role set.';
            }
            if (!$this->join_date) {
                $issues['no_join_date'] = 'No join date, so work anniversaries and the headcount report leave them out.';
            }
            if (!$this->date_of_birth && !$this->gift_card_opt_out) {
                $issues['no_birthday'] = 'No date of birth, so they get no birthday gift card or reminder.';
            }
            if ($this->manager && $this->manager->status !== 'active') {
                $issues['manager_left'] = "Reports to {$this->manager->name}, who has left.";
            }
        } elseif ($this->status === 'resigned') {
            if ($n = $this->countOf('assets')) {
                $issues['left_with_assets'] = "Has left but still holds {$n} asset(s).";
            }
            if ($n = $this->countOf('digitalProducts')) {
                $issues['left_with_licences'] = "Has left but still has {$n} digital product licence(s).";
            }
            if ($this->countOf('workstations')) {
                $desks = $this->relationLoaded('workstations') ? $this->workstations->pluck('name')->join(', ') : null;
                $issues['left_still_seated'] = 'Has left but is still seated at ' . ($desks ?: 'a desk') . '.';
            }
            if ($n = $this->activeReportsCount()) {
                $issues['left_with_reports'] = "Has left but {$n} active employee(s) still report to them.";
            }
            if (!$this->resigned_date) {
                $issues['no_resigned_date'] = 'Marked as resigned with no resignation date, so turnover reports cannot place them.';
            }
        }

        return $issues;
    }

    /**
     * Adds the counts recordIssues() and onboardingSteps() use, so a list doesn't query per row.
     */
    public function scopeWithRecordCounts(Builder $query): void
    {
        $query->withCount([
            'assets',
            'digitalProducts',
            'workstations',
            'subordinates as active_subordinates_count' => fn (Builder $s) => $s->where('status', 'active'),
            'assets as unsigned_assets_count' => fn (Builder $a) => $a->whereNull('agreement_signed_at'),
        ]);
    }

    private function countOf(string $relation): int
    {
        $attribute = Str::snake($relation) . '_count';

        if (array_key_exists($attribute, $this->attributes)) {
            return (int) $this->attributes[$attribute];
        }

        return $this->relationLoaded($relation) ? $this->$relation->count() : $this->$relation()->count();
    }

    private function activeReportsCount(): int
    {
        if (array_key_exists('active_subordinates_count', $this->attributes)) {
            return (int) $this->attributes['active_subordinates_count'];
        }

        return $this->relationLoaded('subordinates')
            ? $this->subordinates->where('status', 'active')->count()
            : $this->subordinates()->where('status', 'active')->count();
    }

    /**
     * Active, not marked onboarded, and joined (or, without a join date, was added) within the
     * last ONBOARDING_WINDOW_DAYS days, or joins in the future.
     */
    public function isOnboarding(): bool
    {
        if ($this->status !== 'active' || $this->onboarded_at) {
            return false;
        }

        $since = now()->subDays(self::ONBOARDING_WINDOW_DAYS)->startOfDay();

        return $this->join_date ? $this->join_date->gte($since) : (bool) $this->created_at?->gte($since);
    }

    public function scopeOnboarding(Builder $query): void
    {
        $since = now()->subDays(self::ONBOARDING_WINDOW_DAYS)->startOfDay();

        $query->where('status', 'active')
            ->whereNull('onboarded_at')
            ->where(fn (Builder $q) => $q->whereDate('join_date', '>=', $since)
                ->orWhere(fn (Builder $added) => $added->whereNull('join_date')->where('created_at', '>=', $since)));
    }

    /**
     * The new joiner's set-up steps, each worked out from their records:
     * key, label, done, and a detail line saying what's there or missing.
     *
     * @return list<array{key: string, label: string, done: bool, detail: string}>
     */
    public function onboardingSteps(): array
    {
        $assets = $this->countOf('assets');
        $unsigned = array_key_exists('unsigned_assets_count', $this->attributes)
            ? (int) $this->attributes['unsigned_assets_count']
            : ($this->relationLoaded('assets') ? $this->assets->whereNull('agreement_signed_at')->count() : $this->assets()->whereNull('agreement_signed_at')->count());
        $desks = $this->countOf('workstations');
        $licences = $this->countOf('digitalProducts');

        $missing = array_values(array_filter([
            $this->role_id ? null : 'role',
            ($this->team_id || $this->is_manager) ? null : 'team',
            ($this->manager_id || $this->role?->is_top_level) ? null : 'manager',
            $this->join_date ? null : 'join date',
            ($this->date_of_birth || $this->gift_card_opt_out) ? null : 'date of birth',
        ]));

        return [
            [
                'key' => 'desk',
                'label' => 'Seat them at a desk',
                'done' => $desks > 0,
                'detail' => $desks
                    ? 'Sits at ' . ($this->relationLoaded('workstations') ? $this->workstations->pluck('name')->join(', ') : 'a desk') . '.'
                    : 'Not seated yet.',
            ],
            [
                'key' => 'equipment',
                'label' => 'Assign their laptop and equipment',
                'done' => $assets > 0,
                'detail' => $assets ? "{$assets} asset(s) assigned." : 'Nothing assigned yet.',
            ],
            [
                'key' => 'agreements',
                'label' => 'Get asset agreements signed',
                'done' => $assets > 0 && $unsigned === 0,
                'detail' => $assets === 0
                    ? 'Assign equipment first.'
                    : ($unsigned === 0 ? 'All signed.' : ($assets - $unsigned) . " of {$assets} signed."),
            ],
            [
                'key' => 'licences',
                'label' => 'Give software licences',
                'done' => $licences > 0,
                'detail' => $licences ? "{$licences} licence(s) assigned." : 'No digital products assigned yet.',
            ],
            [
                'key' => 'profile',
                'label' => 'Complete their profile',
                'done' => $missing === [],
                'detail' => $missing === [] ? 'Role, team, manager and dates are all set.' : 'Missing ' . implode(', ', $missing) . '.',
            ],
        ];
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

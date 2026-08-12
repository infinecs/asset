<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'name', 'id_number', 'work_location', 'email', 'status', 'date_of_birth', 'gift_card_opt_out',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'gift_card_opt_out' => 'boolean',
    ];

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

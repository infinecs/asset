<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = ['name', 'code', 'building', 'floor', 'room', 'postcode', 'city', 'state', 'description', 'floor_plan_path'];

    protected static function booted(): void
    {
        // Workstation codes embed the location code, so rebuild them when it changes.
        static::updated(function (Location $location) {
            if ($location->wasChanged('code')) {
                $location->workstations()->get()->each->save();
            }
        });

        static::deleted(function (Location $location) {
            if ($location->floor_plan_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($location->floor_plan_path);
            }
        });
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function workstations(): HasMany
    {
        return $this->hasMany(Workstation::class);
    }
}

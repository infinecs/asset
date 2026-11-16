<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = ['name', 'code', 'building', 'floor', 'room', 'postcode', 'city', 'state', 'description'];

    protected static function booted(): void
    {
        // Workstation codes embed the location code, so rebuild them when it changes.
        static::updated(function (Location $location) {
            if ($location->wasChanged('code')) {
                $location->workstations()->get()->each->save();
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

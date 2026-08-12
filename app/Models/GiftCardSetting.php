<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GiftCardSetting extends Model
{
    protected $fillable = [];

    public function personsInCharge(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'gift_card_setting_person_in_charge')
            ->withTimestamps();
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::create([]);
    }
}

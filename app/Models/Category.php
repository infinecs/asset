<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function requestTickets(): HasMany
    {
        return $this->hasMany(RequestTicket::class);
    }
}

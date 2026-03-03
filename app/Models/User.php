<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'department', 'phone',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'assigned_to');
    }

    public function requestTickets(): HasMany
    {
        return $this->hasMany(RequestTicket::class, 'requested_by');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(RequestTicket::class, 'assigned_to');
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class, 'lessee_id');
    }

    public function issuedLeases(): HasMany
    {
        return $this->hasMany(Lease::class, 'issued_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'admin';
    }
}

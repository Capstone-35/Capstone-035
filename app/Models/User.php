<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'is_demo',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['kasir', 'kitchen', 'manager', 'owner']);
    }

    public function homeRoute(): string
    {
        if ($this->hasRole('owner')) {
            return route('owner.dashboard');
        }
        if ($this->hasRole('manager')) {
            return route('manager.dashboard');
        }
        if ($this->hasRole('kitchen')) {
            return route('kds.index');
        }
        if ($this->hasRole('kasir')) {
            return route('pos.dashboard');
        }
        if ($this->hasRole('member')) {
            return route('member.dashboard');
        }

        return route('menu.index');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ASTRONAUT = 'astronaut';
    public const ROLE_MEDICAL_OFFICER = 'medical_officer';
    public const ROLE_ADMIN = 'admin';

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function crewProfile(): HasOne
    {
        return $this->hasOne(CrewProfile::class);
    }

    public function isAstronaut(): bool
    {
        return $this->role === self::ROLE_ASTRONAUT;
    }
}

<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements MustVerifyEmail
{
    public const ROLE_CITIZEN = 'ciudadano';
    public const ROLE_FUNCTIONARY = 'funcionario';
    public const ROLE_SUPER_ADMIN = 'super-admin';

    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    /**
     * NUNCA loggear cambios de password ni datos sensibles del ciudadano.
     * Solo se auditan cambios de identidad publica, rol y estado.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'last_name', 'email', 'role', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    protected $fillable = [
        'national_id',
        'name',
        'last_name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isCitizen(): bool
    {
        return $this->role === self::ROLE_CITIZEN;
    }

    public function isFunctionary(): bool
    {
        return $this->role === self::ROLE_FUNCTIONARY;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_FUNCTIONARY, self::ROLE_SUPER_ADMIN], true);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->name . ' ' . ($this->last_name ?? ''));
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }
}

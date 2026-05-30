<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role_id', 'created_by', 'updated_by'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleEnum::SUPER_ADMIN);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleEnum::ADMIN);
    }

    public function isStaff(): bool
    {
        return $this->hasRole(RoleEnum::STAFF);
    }

    public function hasRole(RoleEnum $role): bool
    {
        return $this->role?->slug === $role->slug();
    }
}

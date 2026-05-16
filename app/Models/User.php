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

#[Fillable(['name', 'email', 'password', 'role_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
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

    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->slug === RoleEnum::SUPER_ADMIN->slug();
    }

    public function isSupervisor(): bool
    {
        return $this->role?->slug === RoleEnum::SUPERVISOR->slug();
    }

    public function isGestor(): bool
    {
        return $this->role?->slug === RoleEnum::GESTOR->slug();
    }

    public function isProjectManager(): bool
    {
        return $this->role?->slug === RoleEnum::PROJECT_MANAGER->slug();
    }

    public function isTeamMember(): bool
    {
        return $this->role?->slug === RoleEnum::TEAM_MEMBER->slug();
    }

    public function isViewer(): bool
    {
        return $this->role?->slug === RoleEnum::VIEWER->slug();
    }

    public function canBypassPolicies(): bool
    {
        return $this->isSuperAdmin() || $this->isSupervisor();
    }

    public function hasRole(RoleEnum $role): bool
    {
        return $this->role?->slug === $role->slug();
    }

    public function roleLevel(): int
    {
        return $this->role?->level ?? 0;
    }
}

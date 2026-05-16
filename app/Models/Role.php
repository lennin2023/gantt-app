<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'level'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === RoleEnum::SUPER_ADMIN->slug();
    }

    public function isSupervisor(): bool
    {
        return $this->slug === RoleEnum::SUPERVISOR->slug();
    }

    public function isGestor(): bool
    {
        return $this->slug === RoleEnum::GESTOR->slug();
    }

    public function isProjectManager(): bool
    {
        return $this->slug === RoleEnum::PROJECT_MANAGER->slug();
    }

    public function isTeamMember(): bool
    {
        return $this->slug === RoleEnum::TEAM_MEMBER->slug();
    }

    public function isViewer(): bool
    {
        return $this->slug === RoleEnum::VIEWER->slug();
    }
}

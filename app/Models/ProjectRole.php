<?php

namespace App\Models;

use App\Enums\ProjectRoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRole extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'level'];

    public function projectUsers(): HasMany
    {
        return $this->hasMany(ProjectUser::class);
    }

    public function isSupervisor(): bool
    {
        return $this->slug === ProjectRoleEnum::SUPERVISOR->slug();
    }

    public function isProjectManager(): bool
    {
        return $this->slug === ProjectRoleEnum::PROJECT_MANAGER->slug();
    }

    public function isDeveloper(): bool
    {
        return $this->slug === ProjectRoleEnum::DEVELOPER->slug();
    }

    public function isAnalyst(): bool
    {
        return $this->slug === ProjectRoleEnum::ANALYST->slug();
    }

    public function isDesigner(): bool
    {
        return $this->slug === ProjectRoleEnum::DESIGNER->slug();
    }

    public function isTester(): bool
    {
        return $this->slug === ProjectRoleEnum::TESTER->slug();
    }

    public function isViewer(): bool
    {
        return $this->slug === ProjectRoleEnum::VIEWER->slug();
    }
}

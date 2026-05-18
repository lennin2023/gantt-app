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

    protected function casts(): array
    {
        return [
            'id' => ProjectRoleEnum::class,
        ];
    }

    public function isManager(): bool
    {
        return $this->level >= ProjectRoleEnum::MANAGER_LEVEL;
    }

    public function isExecutor(): bool
    {
        return $this->level === ProjectRoleEnum::EXECUTOR_LEVEL;
    }

    public function isSpectator(): bool
    {
        return $this->level === ProjectRoleEnum::SPECTATOR_LEVEL;
    }

    public function isRole(ProjectRoleEnum $roleEnum): bool
    {
        return $this->id === $roleEnum;
    }

    public function projectUsers(): HasMany
    {
        return $this->hasMany(ProjectUser::class);
    }
}

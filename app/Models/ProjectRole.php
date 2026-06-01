<?php

namespace App\Models;

use App\Enums\ProjectRoleEnum;
use App\Models\Concerns\HasCreatedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRole extends Model
{
    use HasCreatedBy;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isProjectManager(): bool
    {
        return $this->id === ProjectRoleEnum::PROJECT_MANAGER->value;
    }

    public function isTeamMember(): bool
    {
        return $this->id === ProjectRoleEnum::TEAM_MEMBER->value;
    }

    public function isViewer(): bool
    {
        return $this->id === ProjectRoleEnum::VIEWER->value;
    }

    public function isRole(ProjectRoleEnum $roleEnum): bool
    {
        return $this->id === $roleEnum->value;
    }

    public function projectUsers(): HasMany
    {
        return $this->hasMany(ProjectUser::class);
    }
}

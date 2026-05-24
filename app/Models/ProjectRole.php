<?php

namespace App\Models;

use App\Enums\ProjectRoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRole extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'level',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'level' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
        return $this->id === $roleEnum->value;
    }

    public function projectUsers(): HasMany
    {
        return $this->hasMany(ProjectUser::class);
    }
}

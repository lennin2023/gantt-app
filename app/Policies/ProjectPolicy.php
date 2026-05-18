<?php

namespace App\Policies;

use App\Enums\ProjectRoleEnum;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->id === $project->created_by) {
            return true;
        }

        return $project->projectUsers()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->id === $project->created_by) {
            return true;
        }

        return $project->projectUsers()
            ->where('user_id', $user->id)
            ->whereHas('projectRole', fn ($q) => $q->where('level', '>=', ProjectRoleEnum::MIN_LEVEL_MANAGE_PROJECT))
            ->exists();
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->created_by;
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->id === $project->created_by;
    }
}

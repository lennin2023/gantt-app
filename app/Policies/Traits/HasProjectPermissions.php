<?php

namespace App\Policies\Traits;

use App\Enums\ProjectRoleEnum;
use App\Models\Project;
use App\Models\User;

trait HasProjectPermissions
{
    private function isProjectMember(User $user, Project $project): bool
    {
        if ($user->id === $project->created_by) {
            return true;
        }

        return $project->projectUsers()->where('user_id', $user->id)->exists();
    }

    private function canManageProjectResources(User $user, Project $project): bool
    {
        if ($user->id === $project->created_by) {
            return true;
        }

        return $project->projectUsers()
            ->where('user_id', $user->id)
            ->whereHas('projectRole', fn ($q) => $q->where('level', '>=', ProjectRoleEnum::MANAGER_LEVEL))
            ->exists();
    }
}

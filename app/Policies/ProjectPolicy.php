<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function viewDashboard(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $project->created_by;
    }

    public function create(User $user): bool
    {
        return $user->roleLevel() >= RoleEnum::PROJECT_MANAGER->level();
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $project->created_by;
    }

    public function delete(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $project->created_by;
    }

    public function restore(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $project->created_by;
    }
}

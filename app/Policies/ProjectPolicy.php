<?php

namespace App\Policies;

use App\Enums\ProjectRoleEnum;
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
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($user->id === $project->created_by) {
            return true;
        }

        $projectUser = $project->projectUsers()->where('user_id', $user->id)->first();

        return $projectUser !== null;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($user->id === $project->created_by) {
            return true;
        }

        $projectUser = $project->projectUsers()
            ->where('user_id', $user->id)
            ->whereHas('projectRole', fn ($q) => $q->where('level', '>=', ProjectRoleEnum::MIN_LEVEL_MANAGE_PROJECT))
            ->first();

        return $projectUser !== null;
    }

    public function delete(User $user, Project $project): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        return $user->id === $project->created_by;
    }

    public function restore(User $user, Project $project): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        return $user->id === $project->created_by;
    }
}

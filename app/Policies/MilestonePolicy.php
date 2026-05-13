<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;

class MilestonePolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $project->created_by;
    }

    public function view(User $user, Milestone $milestone): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $milestone->project->created_by;
    }

    public function create(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->roleLevel() >= RoleEnum::PROJECT_MANAGER->level() && $user->id === $project->created_by;
    }

    public function update(User $user, Milestone $milestone): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $milestone->project->created_by;
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $milestone->project->created_by;
    }

    public function restore(User $user, Milestone $milestone): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $milestone->project->created_by;
    }
}

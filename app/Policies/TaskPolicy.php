<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $project->created_by;
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $task->project->created_by;
    }

    public function create(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->roleLevel() >= RoleEnum::PROJECT_MANAGER->level() && $user->id === $project->created_by;
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->roleLevel() >= RoleEnum::PROJECT_MANAGER->level() && $user->id === $task->project->created_by) {
            return true;
        }

        return $user->roleLevel() >= RoleEnum::DEVELOPER->level() && (string) $task->assignee === (string) $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $task->project->created_by;
    }

    public function restore(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $task->project->created_by;
    }
}

<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->id === $project->created_by;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->project->created_by;
    }

    public function create(User $user, Project $project): bool
    {
        return $user->id === $project->created_by;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->project->created_by;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->project->created_by;
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->id === $task->project->created_by;
    }
}

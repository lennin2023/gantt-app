<?php

namespace App\Policies;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;

class MilestonePolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function view(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->project->user_id;
    }

    public function create(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function update(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->project->user_id;
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->project->user_id;
    }
}

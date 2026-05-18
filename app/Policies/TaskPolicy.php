<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\Traits\HasProjectPermissions;

class TaskPolicy
{
    use HasProjectPermissions;

    public function before(User $user): ?bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user, Project $project): bool
    {
        return $this->isProjectMember($user, $project);
    }

    public function view(User $user, Project $project): bool
    {
        return $this->isProjectMember($user, $project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->canManageProjectResources($user, $project);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->canManageProjectResources($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->canManageProjectResources($user, $project);
    }

    public function restore(User $user, Project $project): bool
    {
        return $this->canManageProjectResources($user, $project);
    }
}

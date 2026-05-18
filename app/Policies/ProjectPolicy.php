<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\Traits\HasProjectPermissions;

class ProjectPolicy
{
    use HasProjectPermissions;

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
        return $this->isProjectMember($user, $project);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Project $project): bool
    {
        return $this->canManageProjectResources($user, $project);
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

<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\Traits\HasProjectPermissions;

class ProjectUserPolicy
{
    use HasProjectPermissions;

    public function before(User $user): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user, Project $project): bool
    {
        return $this->isProjectMember($user, $project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->isAdmin() || $this->isProjectManager($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin() || $this->isProjectManager($user, $project);
    }
}

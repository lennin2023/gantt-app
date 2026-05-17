<?php

namespace App\Repositories\Eloquent;

use App\Models\ProjectUser;
use App\Repositories\Contracts\ProjectUserRepositoryInterface;
use Illuminate\Support\Collection;

class ProjectUserRepository implements ProjectUserRepositoryInterface
{
    public function getAllByProject(int $projectId): Collection
    {
        return ProjectUser::with(['user', 'projectRole', 'creator'])
            ->where('project_id', $projectId)
            ->get();
    }

    public function getByProjectAndRole(int $projectId, int $projectRoleId): Collection
    {
        return ProjectUser::with(['user', 'projectRole', 'creator'])
            ->where('project_id', $projectId)
            ->where('project_role_id', $projectRoleId)
            ->get();
    }

    public function findByProjectAndUser(int $projectId, int $userId): ?ProjectUser
    {
        return ProjectUser::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $data): ProjectUser
    {
        return ProjectUser::create($data);
    }

    public function delete(ProjectUser $projectUser): bool
    {
        return $projectUser->delete();
    }

    public function exists(int $projectId, int $userId): bool
    {
        return ProjectUser::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->exists();
    }
}

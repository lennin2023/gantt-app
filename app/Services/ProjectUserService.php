<?php

namespace App\Services;

use App\Events\ProjectUserAssigned;
use App\Events\ProjectUserRemoved;
use App\Exceptions\ProjectUserAlreadyAssignedException;
use App\Exceptions\ProjectUserNotFoundException;
use App\Models\ProjectUser;
use App\Repositories\Contracts\ProjectUserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectUserService
{
    public function __construct(
        private readonly ProjectUserRepositoryInterface $projectUserRepository,
    ) {}

    public function getProjectUsers(int $projectId): Collection
    {
        return $this->projectUserRepository->getAllByProject($projectId);
    }

    public function getProjectUsersByRole(int $projectId, int $projectRoleId): Collection
    {
        return $this->projectUserRepository->getByProjectAndRole($projectId, $projectRoleId);
    }

    public function assignUser(int $projectId, int $userId, int $projectRoleId, int $createdBy): ProjectUser
    {
        if ($this->projectUserRepository->exists($projectId, $userId)) {
            throw new ProjectUserAlreadyAssignedException;
        }

        return DB::transaction(function () use ($projectId, $userId, $projectRoleId, $createdBy) {
            $projectUser = $this->projectUserRepository->create([
                'project_id' => $projectId,
                'user_id' => $userId,
                'project_role_id' => $projectRoleId,
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);

            ProjectUserAssigned::dispatch($projectUser);

            return $projectUser;
        });
    }

    public function removeUser(int $projectId, int $userId): void
    {
        DB::transaction(function () use ($projectId, $userId) {
            $projectUser = $this->projectUserRepository->findByProjectAndUser($projectId, $userId);

            if (! $projectUser) {
                throw new ProjectUserNotFoundException;
            }

            $this->projectUserRepository->delete($projectUser);

            ProjectUserRemoved::dispatch($projectUser);
        });
    }

    public function findByProjectAndUser(int $projectId, int $userId): ?ProjectUser
    {
        return $this->projectUserRepository->findByProjectAndUser($projectId, $userId);
    }
}

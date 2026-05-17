<?php

namespace App\Repositories\Contracts;

use App\Models\ProjectUser;
use Illuminate\Support\Collection;

interface ProjectUserRepositoryInterface
{
    public function getAllByProject(int $projectId): Collection;

    public function getByProjectAndRole(int $projectId, int $projectRoleId): Collection;

    public function findById(int $id): ?ProjectUser;

    public function findByProjectAndUser(int $projectId, int $userId): ?ProjectUser;

    public function create(array $data): ProjectUser;

    public function delete(ProjectUser $projectUser): bool;

    public function exists(int $projectId, int $userId): bool;
}

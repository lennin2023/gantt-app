<?php

namespace App\Services;

use App\DTOs\ProjectDTO;
use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    public function getUserProjects(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->projectRepository->getAllByUser($userId, $perPage);
    }

    public function findById(int $id, array $with = []): ?Project
    {
        return $this->projectRepository->findById($id, $with);
    }

    public function createProject(ProjectDTO $dto): Project
    {
        $project = $this->projectRepository->create($dto->toArray());
        return $this->projectRepository->findById($project->id);
    }

    public function updateProject(Project $project, ProjectDTO $dto): Project
    {
        return $this->projectRepository->update($project, $dto->toArray());
    }

    public function deleteProject(Project $project): bool
    {
        return $this->projectRepository->delete($project);
    }
}

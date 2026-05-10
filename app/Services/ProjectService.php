<?php

namespace App\Services;

use App\DTOs\ProjectDTO;
use App\Events\ProjectCreated;
use App\Events\ProjectDeleted;
use App\Events\ProjectUpdated;
use App\Models\Project;
use App\Models\ProjectHistory;
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
        $project = $this->projectRepository->findById($project->id);

        $this->logStatusChange($project, $dto->projectStatusId);

        ProjectCreated::dispatch($project);

        return $project;
    }

    public function updateProject(Project $project, ProjectDTO $dto): Project
    {
        $project = $this->projectRepository->update($project, $dto->toArray());

        ProjectUpdated::dispatch($project);

        return $project;
    }

    public function deleteProject(Project $project): bool
    {
        ProjectDeleted::dispatch($project);

        return $this->projectRepository->delete($project);
    }

    public function restoreProject(int $id): bool
    {
        return $this->projectRepository->restore($id);
    }

    private function logStatusChange(Project $project, int $statusId): void
    {
        ProjectHistory::create([
            'project_id' => $project->id,
            'project_status_id' => $statusId,
            'created_by' => $project->created_by,
            'created_at' => now(),
        ]);
    }
}

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
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($dto) {
            $project = $this->projectRepository->create($dto->toArray());

            $this->logStatusChange($project, $project->project_status_id, $project->created_by);

            ProjectCreated::dispatch($project);

            return $project;
        });
    }

    public function updateProject(Project $project, ProjectDTO $dto): Project
    {
        return DB::transaction(function () use ($project, $dto) {
            $previousStatusId = $project->project_status_id;

            $project = $this->projectRepository->update($project, $dto->toArray());

            if ($project->project_status_id !== $previousStatusId) {
                $this->logStatusChange($project, $project->project_status_id, $project->updated_by);
            }

            ProjectUpdated::dispatch($project);

            return $project;
        });
    }

    public function deleteProject(Project $project): bool
    {
        return DB::transaction(function () use ($project) {
            ProjectDeleted::dispatch($project);

            return $this->projectRepository->delete($project);
        });
    }

    public function restoreProject(Project $project): bool
    {
        return DB::transaction(function () use ($project) {
            return $this->projectRepository->restore($project);
        });
    }

    private function logStatusChange(Project $project, int $statusId, int $userId): void
    {
        ProjectHistory::create([
            'project_id' => $project->id,
            'project_status_id' => $statusId,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }
}

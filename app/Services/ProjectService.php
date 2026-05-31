<?php

namespace App\Services;

use App\DTOs\ProjectDTO;
use App\Enums\ProjectStatusEnum;
use App\Events\ProjectCreated;
use App\Events\ProjectUpdated;
use App\Exceptions\ProjectAlreadyInStatusException;
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

    public function getUserProjects(int $userId, int $perPage = 10, ?int $statusId = null): LengthAwarePaginator
    {
        return $this->projectRepository->getAllByUser($userId, $perPage, $statusId);
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
            $project = $this->projectRepository->update($project, $dto->toArray());

            if ($project->wasChanged('project_status_id')) {
                $this->logStatusChange($project, $project->project_status_id, $dto->updatedBy);
            }

            ProjectUpdated::dispatch($project);

            return $project;
        });
    }

    public function changeStatus(Project $project, ProjectStatusEnum $status, int $userId): void
    {
        if ($project->project_status_id === $status->value) {
            throw new ProjectAlreadyInStatusException($status);
        }

        DB::transaction(function () use ($project, $status, $userId) {
            $project->project_status_id = $status->value;
            $project->updated_by = $userId;
            $project->save();

            $this->logStatusChange($project, $project->project_status_id, $userId);

            ProjectUpdated::dispatch($project);
        });
    }

    public function getProjectDetail(Project $project): Project
    {
        $project->load([
            'projectUsers.user',
            'projectUsers.projectRole',
            'milestones.creator',
        ]);

        return $project;
    }

    public function getProjectStats(Project $project): array
    {
        return $project->getStats();
    }

    private function logStatusChange(Project $project, int $statusId, ?int $userId): void
    {
        ProjectHistory::create([
            'project_id' => $project->id,
            'project_status_id' => $statusId,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }
}

<?php

namespace App\Services;

use App\DTOs\ProjectDTO;
use App\Enums\ProjectStatusEnum;
use App\Events\ProjectCreated;
use App\Events\ProjectUpdated;
use App\Exceptions\ProjectAlreadyInStatusException;
use App\Exceptions\ProjectArchivedCannotBeUpdatedException;
use App\Exceptions\ProjectInvalidStatusTransitionException;
use App\Exceptions\ProjectNotArchivedException;
use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    private array $allowedTransitions = [
        ProjectStatusEnum::ACTIVE->value => [
            ProjectStatusEnum::ON_HOLD->value,
            ProjectStatusEnum::COMPLETED->value,
            ProjectStatusEnum::CANCELLED->value,
        ],
        ProjectStatusEnum::ON_HOLD->value => [
            ProjectStatusEnum::ACTIVE->value,
            ProjectStatusEnum::CANCELLED->value,
        ],
        ProjectStatusEnum::COMPLETED->value => [
            ProjectStatusEnum::ACTIVE->value,
        ],
        ProjectStatusEnum::CANCELLED->value => [
            ProjectStatusEnum::ACTIVE->value,
        ],
        ProjectStatusEnum::ARCHIVED->value => [], // ninguna via update
    ];

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

            ProjectCreated::dispatch($project);

            return $project;
        });
    }

    public function updateProject(Project $project, ProjectDTO $dto): Project
    {
        if ($project->project_status_id === ProjectStatusEnum::ARCHIVED->value) {
            throw new ProjectArchivedCannotBeUpdatedException;
        }

        return DB::transaction(function () use ($project, $dto) {
            $data = $dto->toArray();

            if (isset($data['project_status_id'])) {
                $newStatus = ProjectStatusEnum::from((int) $data['project_status_id']);
                $this->validateTransition($project, $newStatus);
            }

            $project = $this->projectRepository->update($project, $data);

            ProjectUpdated::dispatch($project);

            return $project;
        });
    }

    public function archive(Project $project): void
    {
        if ($project->project_status_id === ProjectStatusEnum::ARCHIVED->value) {
            throw new ProjectAlreadyInStatusException(ProjectStatusEnum::ARCHIVED);
        }

        DB::transaction(function () use ($project) {
            $project->project_status_id = ProjectStatusEnum::ARCHIVED->value;
            $project->save();

            ProjectUpdated::dispatch($project);
        });
    }

    public function restore(Project $project): void
    {
        if ($project->project_status_id !== ProjectStatusEnum::ARCHIVED->value) {
            throw new ProjectNotArchivedException;
        }

        DB::transaction(function () use ($project) {
            $project->project_status_id = ProjectStatusEnum::ACTIVE->value;
            $project->save();

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
        return $this->projectRepository->getStats($project->id);
    }

    public function refreshStatus(Project $project, int $updatedBy): void
    {
        if ($project->isProtectedStatus()) {
            return;
        }

        $stats = $this->projectRepository->getStats($project->id);
        $completed = $stats['total_tasks'] > 0
            && $stats['total_tasks'] === $stats['completed_tasks'];

        $project->project_status_id = $completed
            ? ProjectStatusEnum::COMPLETED->value
            : ProjectStatusEnum::ACTIVE->value;

        $project->updated_by = $updatedBy;
        $project->save();
    }

    private function validateTransition(Project $project, ProjectStatusEnum $newStatus): void
    {
        $currentStatus = ProjectStatusEnum::from($project->project_status_id);

        if ($project->project_status_id === $newStatus->value) {
            throw new ProjectAlreadyInStatusException($newStatus);
        }

        $allowed = $this->allowedTransitions[$currentStatus->value] ?? [];

        if (! in_array($newStatus->value, $allowed)) {
            throw new ProjectInvalidStatusTransitionException($currentStatus, $newStatus);
        }
    }
}

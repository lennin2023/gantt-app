<?php

namespace App\Services;

use App\DTOs\TaskDTO;
use App\Enums\ProjectRoleEnum;
use App\Enums\TaskStatusEnum;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskUpdated;
use App\Models\ProjectUser;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function getProjectTasks(int $projectId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->taskRepository->getAllByProject($projectId, $perPage);
    }

    public function findById(int $id): ?Task
    {
        return $this->taskRepository->findById($id);
    }

    public function createTask(TaskDTO $dto): Task
    {
        return DB::transaction(function () use ($dto) {
            $task = $this->taskRepository->create($dto->toArray());

            if (! empty($dto->dependencyIds)) {
                $this->taskRepository->syncDependencies($task, $dto->dependencyIds);
            }

            if ($dto->assignedTo) {
                $this->ensureUserInProject($task->project_id, $dto->assignedTo);
            }

            $task = $this->taskRepository->findById($task->id);

            TaskCreated::dispatch($task);

            return $task;
        });
    }

    public function updateTask(Task $task, TaskDTO $dto): Task
    {
        return DB::transaction(function () use ($task, $dto) {
            $previousStatus = $task->task_status_id;
            $previousAssignedTo = $task->assigned_to;

            $task = $this->taskRepository->update($task, $dto->toArray());

            if (! empty($dto->dependencyIds)) {
                $this->taskRepository->syncDependencies($task, $dto->dependencyIds);
            }

            if ($dto->assignedTo && $dto->assignedTo !== $previousAssignedTo) {
                $this->ensureUserInProject($task->project_id, $dto->assignedTo);
            }

            TaskUpdated::dispatch($task);

            if ($task->task_status_id === TaskStatusEnum::COMPLETED->value
                && $previousStatus !== TaskStatusEnum::COMPLETED->value) {
                TaskCompleted::dispatch($task);
            }

            return $task;
        });
    }

    public function deleteTask(Task $task): bool
    {
        return DB::transaction(function () use ($task) {
            TaskDeleted::dispatch($task);

            return $this->taskRepository->delete($task);
        });
    }

    public function bulkUpdate(Collection $tasks, array $data): Collection
    {
        $allowedFields = ['task_status_id', 'name', 'description', 'assigned_to', 'start_date', 'end_date', 'progress', 'order'];
        $filteredData = array_intersect_key($data, array_flip($allowedFields));

        return DB::transaction(function () use ($tasks, $filteredData) {
            $updated = collect();

            foreach ($tasks as $task) {
                $previousStatus = $task->task_status_id;
                $updatedTask = $this->taskRepository->update($task, $filteredData);

                if (isset($filteredData['assigned_to']) && $filteredData['assigned_to'] !== $task->assigned_to) {
                    $this->ensureUserInProject($task->project_id, $filteredData['assigned_to']);
                }

                $updated->push($updatedTask);

                TaskUpdated::dispatch($updatedTask);

                if ($updatedTask->task_status_id === TaskStatusEnum::COMPLETED->value
                    && $previousStatus !== TaskStatusEnum::COMPLETED->value) {
                    TaskCompleted::dispatch($updatedTask);
                }
            }

            return $updated;
        });
    }

    public function bulkDelete(Collection $tasks): void
    {
        DB::transaction(function () use ($tasks) {
            foreach ($tasks as $task) {
                TaskDeleted::dispatch($task);
                $this->taskRepository->delete($task);
            }
        });
    }

    public function wouldCreateCycle(Task $task, int $newDependencyId): bool
    {
        return $this->taskRepository->wouldCreateCycle($task, $newDependencyId);
    }

    public function restoreTask(Task $task): bool
    {
        return DB::transaction(function () use ($task) {
            return $this->taskRepository->restore($task);
        });
    }

    private function ensureUserInProject(int $projectId, int $userId): void
    {
        $exists = ProjectUser::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->exists();

        if (! $exists) {
            ProjectUser::create([
                'project_id' => $projectId,
                'user_id' => $userId,
                'project_role_id' => ProjectRoleEnum::DEVELOPER->value,
            ]);
        }
    }
}

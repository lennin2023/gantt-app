<?php

namespace App\Services;

use App\DTOs\BulkTaskDTO;
use App\DTOs\TaskDTO;
use App\Enums\TaskDependencyTypeEnum;
use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Exceptions\BulkOperationException;
use App\Exceptions\CycleDetectionException;
use App\Exceptions\TaskAlreadyInStatusException;
use App\Exceptions\TaskDeletedCannotBeUpdatedException;
use App\Exceptions\TaskInvalidStatusTransitionException;
use App\Exceptions\TaskNotDeletedException;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskService
{
    private array $allowedTransitions = [
        TaskStatusEnum::PENDING->value => [
            TaskStatusEnum::IN_PROGRESS->value,
            TaskStatusEnum::ON_HOLD->value,
            TaskStatusEnum::CANCELLED->value,
        ],
        TaskStatusEnum::IN_PROGRESS->value => [
            TaskStatusEnum::PENDING->value,
            TaskStatusEnum::COMPLETED->value,
            TaskStatusEnum::ON_HOLD->value,
            TaskStatusEnum::CANCELLED->value,
        ],
        TaskStatusEnum::COMPLETED->value => [
            TaskStatusEnum::IN_PROGRESS->value,
        ],
        TaskStatusEnum::ON_HOLD->value => [
            TaskStatusEnum::PENDING->value,
            TaskStatusEnum::IN_PROGRESS->value,
            TaskStatusEnum::CANCELLED->value,
        ],
        TaskStatusEnum::CANCELLED->value => [
            TaskStatusEnum::PENDING->value,
        ],
        TaskStatusEnum::DELETED->value => [],
    ];

    private array $deletableStatuses = [
        TaskStatusEnum::PENDING->value,
        TaskStatusEnum::IN_PROGRESS->value,
        TaskStatusEnum::ON_HOLD->value,
        TaskStatusEnum::CANCELLED->value,
    ];

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
            $data = $dto->toArray();

            if (($data['type'] ?? null) === TaskTypeEnum::MILESTONE->value
                && isset($data['start_date'])
                && ! isset($data['end_date'])) {
                $data['end_date'] = $data['start_date'];
            }

            $task = $this->taskRepository->create($data);

            if ($dto->dependencyIds !== TaskDTO::UNDEFINED_ARRAY && ! empty($dto->dependencyIds)) {
                $this->validateNoCycleWouldBeCreated($task, $dto->dependencyIds);
                $this->taskRepository->syncDependencies(
                    $task,
                    $dto->dependencyIds,
                    $dto->dependencyType ?? TaskDependencyTypeEnum::FINISH_TO_START->value,
                );
            }

            $task->load(['status', 'dependencies', 'assignments.projectUser.user', 'assignments.taskRole']);

            TaskCreated::dispatch($task);

            return $task;
        });
    }

    public function updateTask(Task $task, TaskDTO $dto): Task
    {
        if ($task->task_status_id === TaskStatusEnum::DELETED->value) {
            throw new TaskDeletedCannotBeUpdatedException;
        }

        return DB::transaction(function () use ($task, $dto) {
            $previousStatus = $task->task_status_id;
            $data = $dto->toArray();

            if (isset($data['task_status_id'])) {
                $newStatus = TaskStatusEnum::from((int) $data['task_status_id']);
                $this->validateTransition($task, $newStatus);

                // Containers no permiten cambio manual de status
                if ($task->type === TaskTypeEnum::CONTAINER) {
                    unset($data['task_status_id']);
                }
            }

            // Containers no permiten cambio manual de progress
            if ($task->type === TaskTypeEnum::CONTAINER && isset($data['progress'])) {
                unset($data['progress']);
            }

            // Milestone: sincronizar start_date y end_date
            if ($task->type === TaskTypeEnum::MILESTONE && isset($data['start_date'])) {
                $data['end_date'] = $data['start_date'];
            }

            if ($dto->dependencyIds !== TaskDTO::UNDEFINED_ARRAY) {
                $existingIds = $task->dependencies()->pluck('depends_on_task_id')->toArray();
                $newIds = array_diff($dto->dependencyIds, $existingIds);

                if (! empty($newIds)) {
                    $this->validateNoCycleWouldBeCreated($task, $newIds);
                }

                $this->taskRepository->syncDependencies(
                    $task,
                    $dto->dependencyIds,
                    $dto->dependencyType ?? TaskDependencyTypeEnum::FINISH_TO_START->value,
                );
            }

            $task = $this->taskRepository->update($task, $data);

            TaskUpdated::dispatch($task);

            if ($task->task_status_id === TaskStatusEnum::COMPLETED->value
                && $previousStatus !== TaskStatusEnum::COMPLETED->value) {
                TaskCompleted::dispatch($task);
            }

            return $task;
        });
    }

    public function deleteTask(Task $task): void
    {
        if ($task->task_status_id === TaskStatusEnum::DELETED->value) {
            throw new TaskAlreadyInStatusException(TaskStatusEnum::DELETED);
        }

        if (! in_array($task->task_status_id, $this->deletableStatuses)) {
            throw new TaskInvalidStatusTransitionException(
                TaskStatusEnum::from($task->task_status_id),
                TaskStatusEnum::DELETED
            );
        }

        DB::transaction(function () use ($task) {
            // Cascadear DELETED a todos los descendientes
            $this->taskRepository->getDescendantsByPath($task->path)
                ->whereNotIn('task_status_id', [
                    TaskStatusEnum::DELETED->value,
                    TaskStatusEnum::COMPLETED->value,
                ])
                ->each(function (Task $descendant) {
                    $descendant->task_status_id = TaskStatusEnum::DELETED->value;
                    $descendant->saveQuietly();
                });

            $task->task_status_id = TaskStatusEnum::DELETED->value;
            $task->save();

            TaskUpdated::dispatch($task);
        });
    }

    public function restoreTask(Task $task): void
    {
        if ($task->task_status_id !== TaskStatusEnum::DELETED->value) {
            throw new TaskNotDeletedException;
        }

        DB::transaction(function () use ($task) {
            // Restaurar descendientes eliminados
            $this->taskRepository->getDescendantsByPath($task->path)
                ->where('task_status_id', TaskStatusEnum::DELETED->value)
                ->each(function (Task $descendant) {
                    $descendant->task_status_id = TaskStatusEnum::PENDING->value;
                    $descendant->saveQuietly();
                });

            $task->task_status_id = TaskStatusEnum::PENDING->value;
            $task->save();

            TaskUpdated::dispatch($task);
        });
    }

    public function validateAndGetTasksForBulkUpdate(array $taskIds): Collection
    {
        if (empty($taskIds)) {
            throw BulkOperationException::noTaskIdsProvided();
        }

        $tasks = $this->taskRepository->findByIds($taskIds);

        if ($tasks->isEmpty()) {
            throw BulkOperationException::tasksNotFound();
        }

        $projectIds = $tasks->pluck('project_id')->unique();

        if ($projectIds->count() > 1) {
            throw BulkOperationException::tasksMustBelongToSameProject();
        }

        return $tasks;
    }

    public function bulkUpdate(Collection $tasks, BulkTaskDTO $dto): Collection
    {
        return DB::transaction(function () use ($tasks, $dto) {
            $result = collect();

            foreach ($tasks as $task) {
                if ($task->task_status_id === TaskStatusEnum::DELETED->value) {
                    continue;
                }

                $previousStatus = $task->task_status_id;
                $updatedTask = $this->taskRepository->update($task, $dto->toArray());
                $updatedTask->load(['status', 'assignments.projectUser.user', 'assignments.taskRole']);

                $result->push($updatedTask);

                TaskUpdated::dispatch($updatedTask);

                if ($updatedTask->task_status_id === TaskStatusEnum::COMPLETED->value
                    && $previousStatus !== TaskStatusEnum::COMPLETED->value) {
                    TaskCompleted::dispatch($updatedTask);
                }
            }

            return $result;
        });
    }

    public function validateNoCycleWouldBeCreated(Task $task, array $dependencyIds): void
    {
        foreach ($dependencyIds as $depId) {
            if ($this->taskRepository->wouldCreateCycle($task, $depId)) {
                throw new CycleDetectionException;
            }
        }
    }

    private function validateTransition(Task $task, TaskStatusEnum $newStatus): void
    {
        $currentStatus = TaskStatusEnum::from($task->task_status_id);

        if ($task->task_status_id === $newStatus->value) {
            throw new TaskAlreadyInStatusException($newStatus);
        }

        if ($task->type === TaskTypeEnum::MILESTONE) {
            $milestoneAllowed = [
                TaskStatusEnum::PENDING->value => [TaskStatusEnum::COMPLETED->value, TaskStatusEnum::CANCELLED->value],
                TaskStatusEnum::COMPLETED->value => [TaskStatusEnum::PENDING->value],
                TaskStatusEnum::CANCELLED->value => [TaskStatusEnum::PENDING->value],
            ];

            $allowed = $milestoneAllowed[$currentStatus->value] ?? [];

            if (! in_array($newStatus->value, $allowed)) {
                throw new TaskInvalidStatusTransitionException($currentStatus, $newStatus);
            }

            return;
        }

        $allowed = $this->allowedTransitions[$currentStatus->value] ?? [];

        if (! in_array($newStatus->value, $allowed)) {
            throw new TaskInvalidStatusTransitionException($currentStatus, $newStatus);
        }
    }
}

<?php

namespace App\Services;

use App\DTOs\TaskDTO;
use App\Enums\TaskDependencyTypeEnum;
use App\Enums\TaskStatusEnum;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Exceptions\BulkOperationException;
use App\Exceptions\CycleDetectionException;
use App\Exceptions\TaskAlreadyInStatusException;
use App\Exceptions\TaskNotCancelledException;
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
        return DB::transaction(function () use ($task, $dto) {
            $previousStatus = $task->task_status_id;

            $task = $this->taskRepository->update($task, $dto->toArray());

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

            TaskUpdated::dispatch($task);

            if ($task->task_status_id === TaskStatusEnum::COMPLETED->value
                && $previousStatus !== TaskStatusEnum::COMPLETED->value) {
                TaskCompleted::dispatch($task);
            }

            return $task;
        });
    }

    public function cancelTask(Task $task, int $userId): void
    {
        if ($task->task_status_id === TaskStatusEnum::CANCELLED->value) {
            throw new TaskAlreadyInStatusException(TaskStatusEnum::CANCELLED);
        }

        DB::transaction(function () use ($task, $userId) {
            $task->task_status_id = TaskStatusEnum::CANCELLED->value;
            $task->updated_by = $userId;
            $task->save();

            TaskUpdated::dispatch($task);
        });
    }

    public function restoreTask(Task $task, int $userId): void
    {
        if ($task->task_status_id !== TaskStatusEnum::CANCELLED->value) {
            throw new TaskNotCancelledException;
        }

        DB::transaction(function () use ($task, $userId) {
            $task->task_status_id = TaskStatusEnum::PENDING->value;
            $task->updated_by = $userId;
            $task->save();

            TaskUpdated::dispatch($task);
        });
    }

    public function validateAndGetTasksForBulkUpdate(array $taskIds): Collection
    {
        if (empty($taskIds)) {
            throw BulkOperationException::noTaskIdsProvided();
        }

        $tasks = Task::whereIn('id', $taskIds)->get();

        if ($tasks->isEmpty()) {
            throw BulkOperationException::tasksNotFound();
        }

        $projectIds = $tasks->pluck('project_id')->unique();

        if ($projectIds->count() > 1) {
            throw BulkOperationException::tasksMustBelongToSameProject();
        }

        return $tasks;
    }

    public function validateAndGetTasksForBulkDelete(array $taskIds): Collection
    {
        if (empty($taskIds)) {
            throw BulkOperationException::noTaskIdsProvided();
        }

        $tasks = Task::whereIn('id', $taskIds)->get();

        if ($tasks->isEmpty()) {
            throw BulkOperationException::tasksNotFound();
        }

        return $tasks;
    }

    public function bulkUpdate(Collection $tasks, array $data): Collection
    {
        $allowedFields = [
            'task_status_id', 'title', 'description',
            'start_date', 'end_date', 'progress', 'order',
        ];

        $filteredData = array_intersect_key($data, array_flip($allowedFields));

        return DB::transaction(function () use ($tasks, $filteredData) {
            $result = collect();

            foreach ($tasks as $task) {
                $previousStatus = $task->task_status_id;
                $updatedTask = $this->taskRepository->update($task, $filteredData);
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

    public function bulkCancel(Collection $tasks, int $userId): void
    {
        DB::transaction(function () use ($tasks, $userId) {
            foreach ($tasks as $task) {
                if ($task->task_status_id !== TaskStatusEnum::CANCELLED->value) {
                    $task->task_status_id = TaskStatusEnum::CANCELLED->value;
                    $task->updated_by = $userId;
                    $task->save();

                    TaskUpdated::dispatch($task);
                }
            }
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
}

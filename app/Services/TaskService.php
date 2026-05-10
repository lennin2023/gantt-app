<?php

namespace App\Services;

use App\DTOs\TaskDTO;
use App\Enums\TaskStatus;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskUpdated;
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

            $task = $this->taskRepository->findById($task->id);

            TaskCreated::dispatch($task);

            return $task;
        });
    }

    public function updateTask(Task $task, TaskDTO $dto): Task
    {
        return DB::transaction(function () use ($task, $dto) {
            $previousStatus = $task->status;

            $task = $this->taskRepository->update($task, $dto->toArray());

            if (array_key_exists('dependencyIds', $dto->toArray())) {
                $this->taskRepository->syncDependencies($task, $dto->dependencyIds);
            }

            TaskUpdated::dispatch($task);

            if ($task->status === TaskStatus::COMPLETED && $previousStatus !== TaskStatus::COMPLETED) {
                TaskCompleted::dispatch($task);
            }

            return $task;
        });
    }

    public function deleteTask(Task $task): bool
    {
        TaskDeleted::dispatch($task);

        return $this->taskRepository->delete($task);
    }

    public function bulkUpdate(array $taskIds, array $data): Collection
    {
        $allowedFields = ['name', 'description', 'assignee', 'start_date', 'end_date', 'progress', 'status', 'order'];
        $filteredData = array_intersect_key($data, array_flip($allowedFields));

        $tasks = Task::whereIn('id', $taskIds)->get();
        $updated = collect();

        foreach ($tasks as $task) {
            $previousStatus = $task->status;
            $updatedTask = $this->taskRepository->update($task, $filteredData);
            $updated->push($updatedTask);

            TaskUpdated::dispatch($updatedTask);

            if ($updatedTask->status === TaskStatus::COMPLETED && $previousStatus !== TaskStatus::COMPLETED) {
                TaskCompleted::dispatch($updatedTask);
            }
        }

        return $updated;
    }

    public function bulkDelete(array $taskIds): int
    {
        $tasks = Task::whereIn('id', $taskIds)->get();

        foreach ($tasks as $task) {
            TaskDeleted::dispatch($task);
        }

        return $this->taskRepository->deleteMany($taskIds);
    }

    public function wouldCreateCycle(Task $task, int $newDependencyId): bool
    {
        return $this->taskRepository->wouldCreateCycle($task, $newDependencyId);
    }
}

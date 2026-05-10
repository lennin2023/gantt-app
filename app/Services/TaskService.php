<?php

namespace App\Services;

use App\DTOs\TaskDTO;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
        $task = $this->taskRepository->create($dto->toArray());

        if (! empty($dto->dependencyIds)) {
            $this->taskRepository->syncDependencies($task, $dto->dependencyIds);
        }

        return $this->taskRepository->findById($task->id);
    }

    public function updateTask(Task $task, TaskDTO $dto): Task
    {
        $task = $this->taskRepository->update($task, $dto->toArray());

        if (array_key_exists('dependencyIds', $dto->toArray())) {
            $this->taskRepository->syncDependencies($task, $dto->dependencyIds);
        }

        return $task;
    }

    public function deleteTask(Task $task): bool
    {
        return $this->taskRepository->delete($task);
    }

    public function bulkUpdate(array $taskIds, array $data): Collection
    {
        $allowedFields = ['name', 'description', 'assignee', 'start_date', 'end_date', 'progress', 'status', 'order'];
        $filteredData = array_intersect_key($data, array_flip($allowedFields));

        $tasks = Task::whereIn('id', $taskIds)->get();
        $updated = collect();

        foreach ($tasks as $task) {
            $updated->push($this->taskRepository->update($task, $filteredData));
        }

        return $updated;
    }

    public function bulkDelete(array $taskIds): int
    {
        return Task::whereIn('id', $taskIds)->delete();
    }

    public function wouldCreateCycle(Task $task, int $newDependencyId): bool
    {
        return $this->taskRepository->wouldCreateCycle($task, $newDependencyId);
    }
}

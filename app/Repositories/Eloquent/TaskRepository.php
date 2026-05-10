<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskRepository implements TaskRepositoryInterface
{
    public function getAllByProject(int $projectId, int $perPage = 10): LengthAwarePaginator
    {
        return Task::with('dependencies')
            ->where('project_id', $projectId)
            ->orderBy('order')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Task
    {
        return Task::with(['dependencies', 'dependents', 'project'])->find($id);
    }

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);
        return $task->fresh();
    }

    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    public function syncDependencies(Task $task, array $dependencyIds): void
    {
        $task->dependencies()->sync($dependencyIds);
    }

    public function wouldCreateCycle(Task $task, int $newDependencyId): bool
    {
        $visited = [];
        return $this->hasPath($newDependencyId, $task->id, $visited);
    }

    private function hasPath(int $from, int $to, array &$visited): bool
    {
        if ($from === $to) {
            return true;
        }

        if (isset($visited[$from])) {
            return false;
        }

        $visited[$from] = true;

        $task = Task::with('dependents')->find($from);

        foreach ($task->dependents as $dependent) {
            if ($this->hasPath($dependent->id, $to, $visited)) {
                return true;
            }
        }

        return false;
    }
}

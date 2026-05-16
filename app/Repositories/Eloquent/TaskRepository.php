<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    public function getAllByProject(int $projectId, int $perPage = 10): LengthAwarePaginator
    {
        return Task::with(['status', 'dependencies'])
            ->where('project_id', $projectId)
            ->orderBy('order')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Task
    {
        return Task::with(['status', 'dependencies', 'dependents', 'project'])->find($id);
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
        $tasksInProject = Task::with('dependents')
            ->where('project_id', $task->project_id)
            ->get()
            ->keyBy('id');

        $visited = [];

        return $this->hasPathInGraph($newDependencyId, $task->id, $visited, $tasksInProject);
    }

    private function hasPathInGraph(int $from, int $to, array &$visited, Collection $tasksInProject): bool
    {
        if ($from === $to) {
            return true;
        }

        if (isset($visited[$from])) {
            return false;
        }

        $visited[$from] = true;

        $taskNode = $tasksInProject->get($from);

        if (! $taskNode) {
            return false;
        }

        foreach ($taskNode->dependents as $dependent) {
            if ($this->hasPathInGraph($dependent->id, $to, $visited, $tasksInProject)) {
                return true;
            }
        }

        return false;
    }

    public function restore(Task $task): bool
    {
        return $task->restore();
    }
}

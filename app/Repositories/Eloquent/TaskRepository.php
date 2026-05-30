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
        return Task::with([
            'status',
            'assignments.projectUser.user',
            'assignments.taskRole',
            'dependencies',
            'creator',
        ])
            ->where('project_id', $projectId)
            ->orderBy('parent_id')
            ->orderBy('order')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Task
    {
        return Task::with([
            'status',
            'dependencies',
            'dependents',
            'assignments.projectUser.user',
            'assignments.taskRole',
            'parent',
            'children.status',
        ])->find($id);
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

    public function syncDependencies(Task $task, array $dependencyIds, string $type): void
    {
        $syncData = collect($dependencyIds)->mapWithKeys(fn ($id) => [
            $id => ['type' => $type],
        ])->toArray();

        $task->dependencies()->sync($syncData);
    }

    public function wouldCreateCycle(Task $task, int $newDependencyId): bool
    {
        $tasks = Task::with('dependents')
            ->where('project_id', $task->project_id)
            ->get()
            ->keyBy('id');

        $visited = [];

        return $this->hasPathInGraph($newDependencyId, $task->id, $visited, $tasks);
    }

    private function hasPathInGraph(int $from, int $to, array &$visited, Collection $tasks): bool
    {
        if ($from === $to) {
            return true;
        }

        if (isset($visited[$from])) {
            return false;
        }

        $visited[$from] = true;

        $taskNode = $tasks->get($from);

        if (! $taskNode) {
            return false;
        }

        foreach ($taskNode->dependents as $dependent) {
            if ($this->hasPathInGraph($dependent->id, $to, $visited, $tasks)) {
                return true;
            }
        }

        return false;
    }
}

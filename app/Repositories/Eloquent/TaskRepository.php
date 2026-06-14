<?php

namespace App\Repositories\Eloquent;

use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
            'project',
        ])
            ->where('project_id', $projectId)
            ->orderBy('path')
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
            'project',
        ])->find($id);
    }

    public function create(array $data): Task
    {
        // El path se asigna automáticamente en TaskObserver::creating/created
        return Task::create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $oldPath = $task->path;
        $parentChanged = isset($data['parent_id'])
            && $data['parent_id'] !== $task->parent_id;

        $task->update($data);

        if ($parentChanged) {
            $newParentPath = $task->parent_id
                ? Task::findOrFail($task->parent_id)->path
                : null;

            $newPath = $newParentPath
                ? "{$newParentPath}/{$task->id}"
                : (string) $task->id;

            $task->path = $newPath;
            $task->saveQuietly();

            $this->updateDescendantPaths($oldPath, $newPath);
        }

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
        $result = DB::select('
            WITH RECURSIVE dependency_chain AS (
                SELECT depends_on_task_id AS task_id
                FROM task_dependencies
                WHERE task_id = :start_id

                UNION ALL

                SELECT td.depends_on_task_id
                FROM task_dependencies td
                INNER JOIN dependency_chain dc ON td.task_id = dc.task_id
            )
            SELECT 1 as found FROM dependency_chain WHERE task_id = :target_id LIMIT 1
        ', [
            'start_id' => $newDependencyId,
            'target_id' => $task->id,
        ]);

        return ! empty($result);
    }

    public function getLeafTasksAvgProgress(int $parentId, string $path): int
    {
        $result = Task::where('path', 'LIKE', "{$path}/%")
            ->where('type', TaskTypeEnum::TASK->value)
            ->whereNotIn('task_status_id', [
                TaskStatusEnum::CANCELLED->value,
                TaskStatusEnum::DELETED->value,
            ])
            ->whereDoesntHave('children', fn ($q) => $q->where('type', TaskTypeEnum::TASK->value))
            ->avg('progress');

        return (int) round($result ?? 0);
    }

    public function updateDescendantPaths(string $oldPath, string $newPath): void
    {
        Task::where('path', 'LIKE', "{$oldPath}/%")
            ->each(function (Task $task) use ($oldPath, $newPath) {
                $task->path = str_replace($oldPath.'/', $newPath.'/', $task->path);
                $task->saveQuietly();
            });
    }
}

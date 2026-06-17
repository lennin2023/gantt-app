<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\ProjectService;
use App\Services\TaskProgressService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TaskRepository implements TaskRepositoryInterface
{
    private const PAD_LENGTH = 4;

    public function __construct(
        private readonly TaskProgressService $taskProgressService,
        private readonly ProjectService $projectService,
    ) {}

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
        return Task::create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $oldParentId = $task->parent_id;
        $oldPath = $task->path;
        $parentChanged = isset($data['parent_id'])
            && (int) $data['parent_id'] !== (int) $task->parent_id;

        $task->update($data);

        if ($parentChanged) {
            $newParentPath = $task->parent_id
                ? Task::findOrFail($task->parent_id)->path
                : null;

            $segment = $this->nextSegment($task->parent_id, $task->id);

            $newPath = $newParentPath
                ? "{$newParentPath}/{$segment}"
                : $segment;

            $task->path = $newPath;
            $task->saveQuietly();

            $this->updateDescendantPaths($oldPath, $newPath);
            $this->renumberSiblings($oldParentId);

            if ($oldParentId) {
                $this->taskProgressService->recalculateById($oldParentId);
            } else {
                $this->projectService->refreshDates($task->project_id);
            }

            if ($task->parent_id) {
                $this->taskProgressService->recalculateById($task->parent_id);
            } else {
                $this->projectService->refreshDates($task->project_id);
            }
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

    public function updateDescendantPaths(string $oldPath, string $newPath): void
    {
        Task::where('path', 'LIKE', "{$oldPath}/%")
            ->each(function (Task $task) use ($oldPath, $newPath) {
                $task->path = str_replace($oldPath.'/', $newPath.'/', $task->path);
                $task->saveQuietly();
            });
    }

    private function renumberSiblings(?int $parentId): void
    {
        $siblings = Task::where('parent_id', $parentId)
            ->orderBy('path')
            ->get();

        $parentPath = $parentId ? Task::find($parentId)?->path : null;

        foreach ($siblings as $index => $sibling) {
            $segment = str_pad((string) ($index + 1), self::PAD_LENGTH, '0', STR_PAD_LEFT);
            $newPath = $parentPath ? "{$parentPath}/{$segment}" : $segment;

            if ($sibling->path !== $newPath) {
                $oldSiblingPath = $sibling->path;

                $sibling->path = $newPath;
                $sibling->saveQuietly();

                $this->updateDescendantPaths($oldSiblingPath, $newPath);
            }
        }
    }

    private function nextSegment(?int $parentId, int $excludeTaskId): string
    {
        $count = Task::where('parent_id', $parentId)
            ->where('id', '!=', $excludeTaskId)
            ->count();

        return str_pad((string) ($count + 1), self::PAD_LENGTH, '0', STR_PAD_LEFT);
    }
}

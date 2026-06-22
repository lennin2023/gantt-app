<?php

namespace App\Repositories\Eloquent;

use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

    public function findByIds(array $ids): Collection
    {
        return Task::whereIn('id', $ids)->get();
    }

    public function getDescendantsByPath(string $path): Collection
    {
        return Task::where('path', 'LIKE', "{$path}/%")->get();
    }

    public function getActiveRootTasks(int $projectId): Collection
    {
        return Task::where('project_id', $projectId)
            ->whereNull('parent_id')
            ->whereNotIn('task_status_id', [
                TaskStatusEnum::CANCELLED->value,
                TaskStatusEnum::DELETED->value,
            ])
            ->get();
    }

    public function getChildrenForProgressCalc(int $parentId): Collection
    {
        return Task::where('parent_id', $parentId)
            ->whereNotIn('task_status_id', [
                TaskStatusEnum::CANCELLED->value,
                TaskStatusEnum::DELETED->value,
            ])
            ->where('type', '!=', TaskTypeEnum::MILESTONE->value)
            ->get();
    }
}

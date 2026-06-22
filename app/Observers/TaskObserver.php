<?php

namespace App\Observers;

use App\Jobs\RecalculateTaskHierarchyJob;
use App\Models\Task;
use App\Services\TaskPathService;

class TaskObserver
{
    public function __construct(
        private readonly TaskPathService $taskPathService,
    ) {}

    public function creating(Task $task): void
    {
        $parentPath = $task->parent_id
            ? Task::findOrFail($task->parent_id)->path
            : null;

        $task->path = $parentPath
            ? "{$parentPath}/0000"
            : '0000';
    }

    public function created(Task $task): void
    {
        $this->taskPathService->applyPathOnCreate($task);

        RecalculateTaskHierarchyJob::dispatch(
            $task->id,
            $task->project_id,
            recalculateAncestors: (bool) $task->parent_id,
            refreshProjectDates: true,
        );
    }

    public function updated(Task $task): void
    {
        if ($task->wasChanged('parent_id')) {
            $oldParentId = (int) $task->getOriginal('parent_id') ?: null;
            $oldPath = (string) $task->getOriginal('path');

            $this->taskPathService->handleParentChange($task, $oldParentId, $oldPath);

            if ($oldParentId) {
                RecalculateTaskHierarchyJob::dispatch(
                    $oldParentId,
                    $task->project_id,
                    recalculateAncestors: true,
                    refreshProjectDates: false,
                );
            }

            $targetId = $task->parent_id ?? $task->id;
            RecalculateTaskHierarchyJob::dispatch(
                $targetId,
                $task->project_id,
                recalculateAncestors: (bool) $task->parent_id,
                refreshProjectDates: true,
            );

            return;
        }

        if (! $task->wasChanged(['task_status_id', 'progress', 'start_date', 'end_date'])) {
            return;
        }

        RecalculateTaskHierarchyJob::dispatch(
            $task->id,
            $task->project_id,
            recalculateAncestors: (bool) $task->parent_id,
            refreshProjectDates: ! $task->parent_id || $task->wasChanged(['start_date', 'end_date']),
        );
    }
}

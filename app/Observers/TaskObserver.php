<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\ProjectService;
use App\Services\TaskPathService;
use App\Services\TaskProgressService;

class TaskObserver
{
    public function __construct(
        private readonly TaskPathService $taskPathService,
        private readonly TaskProgressService $taskProgressService,
        private readonly ProjectService $projectService,
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

        if ($task->parent_id) {
            $this->taskProgressService->recalculateAncestors($task);
        } else {
            $this->projectService->refreshDates($task->project_id);
        }
    }

    public function updated(Task $task): void
    {
        if ($task->wasChanged('parent_id')) {
            $oldParentId = (int) $task->getOriginal('parent_id') ?: null;
            $oldPath = (string) $task->getOriginal('path');

            $this->taskPathService->handleParentChange($task, $oldParentId, $oldPath);

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

            return;
        }

        if (! $task->wasChanged(['task_status_id', 'progress', 'start_date', 'end_date'])) {
            return;
        }

        if ($task->parent_id) {
            $this->taskProgressService->recalculateAncestors($task);

            return;
        }

        if ($task->wasChanged(['start_date', 'end_date'])) {
            $this->projectService->refreshDates($task->project_id);
        }
    }
}

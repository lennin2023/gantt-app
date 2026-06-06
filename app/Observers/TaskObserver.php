<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\TaskProgressService;

class TaskObserver
{
    public function __construct(
        private readonly TaskProgressService $taskProgressService,
    ) {}

    public function updated(Task $task): void
    {
        if (! $task->parent_id) {
            return;
        }

        if (! $task->wasChanged(['task_status_id', 'progress', 'start_date', 'end_date'])) {
            return;
        }

        $this->taskProgressService->recalculateAncestors($task);
    }

    public function created(Task $task): void
    {
        if (! $task->parent_id) {
            return;
        }

        $this->taskProgressService->recalculateAncestors($task);
    }
}

<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\ProjectService;
use App\Services\TaskProgressService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateTaskHierarchyJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly int $taskId,
        public readonly int $projectId,
        public readonly bool $recalculateAncestors = true,
        public readonly bool $refreshProjectDates = true,
    ) {}

    public function handle(
        TaskProgressService $taskProgressService,
        ProjectService $projectService,
    ): void {
        $task = Task::find($this->taskId);

        if (! $task) {
            return;
        }

        if ($this->recalculateAncestors && $task->parent_id) {
            $taskProgressService->recalculateAncestors($task);
        }

        if ($this->refreshProjectDates) {
            $projectService->refreshDates($this->projectId);
        }
    }
}

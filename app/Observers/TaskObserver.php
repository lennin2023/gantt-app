<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\TaskProgressService;

class TaskObserver
{
    private const PAD_LENGTH = 4;

    public function __construct(
        private readonly TaskProgressService $taskProgressService,
    ) {}

    public function creating(Task $task): void
    {
        $parentPath = $task->parent_id
            ? Task::findOrFail($task->parent_id)->path
            : null;

        // Path temporal con placeholder — se corrige en created()
        $task->path = $parentPath
            ? "{$parentPath}/".str_pad('0', self::PAD_LENGTH, '0', STR_PAD_LEFT)
            : str_pad('0', self::PAD_LENGTH, '0', STR_PAD_LEFT);
    }

    public function created(Task $task): void
    {
        $parentPath = $task->parent_id
            ? Task::findOrFail($task->parent_id)->path
            : null;

        $segment = $this->nextSegment($task->parent_id);

        $task->path = $parentPath
            ? "{$parentPath}/{$segment}"
            : $segment;

        $task->saveQuietly();

        if (! $task->parent_id) {
            return;
        }

        $this->taskProgressService->recalculateAncestors($task);
    }

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

    private function nextSegment(?int $parentId): string
    {
        $count = Task::where('parent_id', $parentId)->count();

        // count incluye el registro recién insertado, así que el número de orden es count (1-indexed)
        return str_pad((string) $count, self::PAD_LENGTH, '0', STR_PAD_LEFT);
    }
}

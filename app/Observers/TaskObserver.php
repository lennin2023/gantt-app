<?php

namespace App\Observers;

use App\Enums\TaskTypeEnum;
use App\Models\Task;
use App\Services\TaskProgressService;

class TaskObserver
{
    public function __construct(
        private readonly TaskProgressService $taskProgressService,
    ) {}

    public function creating(Task $task): void
    {
        $parentPath = $task->parent_id
            ? Task::findOrFail($task->parent_id)->path
            : null;

        // Path temporal — se actualizará con el id real en created
        $task->path = $parentPath ? "{$parentPath}/0" : '0';
    }

    public function created(Task $task): void
    {
        // Asignar path real ahora que tenemos el id
        $parentPath = $task->parent_id
            ? Task::findOrFail($task->parent_id)->path
            : null;

        $task->path = $parentPath
            ? "{$parentPath}/{$task->id}"
            : (string) $task->id;

        $task->saveQuietly();

        // Propagar a ancestros solo si no es container
        if (! $task->parent_id || $task->type === TaskTypeEnum::CONTAINER) {
            return;
        }

        $this->taskProgressService->recalculateAncestors($task);
    }

    public function updated(Task $task): void
    {
        if (! $task->parent_id) {
            return;
        }

        if ($task->type === TaskTypeEnum::CONTAINER) {
            return;
        }

        if (! $task->wasChanged(['task_status_id', 'progress', 'start_date', 'end_date'])) {
            return;
        }

        $this->taskProgressService->recalculateAncestors($task);
    }
}

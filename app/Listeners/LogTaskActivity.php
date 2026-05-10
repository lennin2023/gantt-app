<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogTaskActivity implements ShouldQueue
{
    public function handleTaskCreated(TaskCreated $event): void
    {
        Log::info('Task created', [
            'task_id' => $event->task->id,
            'project_id' => $event->task->project_id,
            'name' => $event->task->name,
        ]);
    }

    public function handleTaskUpdated(TaskUpdated $event): void
    {
        Log::info('Task updated', [
            'task_id' => $event->task->id,
            'project_id' => $event->task->project_id,
            'name' => $event->task->name,
            'changes' => $event->task->getChanges(),
        ]);
    }

    public function handleTaskDeleted(TaskDeleted $event): void
    {
        Log::info('Task deleted', [
            'task_id' => $event->task->id,
            'project_id' => $event->task->project_id,
        ]);
    }

    public function subscribe($events): array
    {
        return [
            TaskCreated::class => 'handleTaskCreated',
            TaskUpdated::class => 'handleTaskUpdated',
            TaskDeleted::class => 'handleTaskDeleted',
        ];
    }
}

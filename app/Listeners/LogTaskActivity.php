<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskEvent;
use App\Events\TaskUpdated;
use Illuminate\Support\Facades\Log;

class LogTaskActivity
{
    public function handle(TaskEvent $event): void
    {
        $action = match ($event::class) {
            TaskCreated::class => 'created',
            TaskUpdated::class => 'updated',
            TaskCompleted::class => 'completed',
            default => 'unknown',
        };

        $task = $event->task();

        Log::info("Task {$action}", [
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'title' => $task->title,
        ]);
    }
}

<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogTaskActivity implements ShouldQueue
{
    public function handle(TaskCompleted|TaskCreated|TaskDeleted|TaskUpdated $event): void
    {
        $action = match (true) {
            $event instanceof TaskCreated => 'created',
            $event instanceof TaskUpdated => 'updated',
            $event instanceof TaskDeleted => 'deleted',
            $event instanceof TaskCompleted => 'completed',
        };

        Log::info("Task {$action}", [
            'task_id' => $event->task->id,
            'project_id' => $event->task->project_id,
            'title' => $event->task->title,
        ]);
    }
}

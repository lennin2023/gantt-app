<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use Illuminate\Support\Facades\Log;

class LogTaskActivity
{
    public function handle(object $event): void
    {
        $action = match (true) {
            $event instanceof TaskCreated => 'created',
            $event instanceof TaskUpdated => 'updated',
            $event instanceof TaskCompleted => 'completed',
            default => 'unknown',
        };

        Log::info("Task {$action}", [
            'task_id' => $event->task->id,
            'project_id' => $event->task->project_id,
            'title' => $event->task->title,
        ]);
    }
}

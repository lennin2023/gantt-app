<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class RefreshProjectStatus implements ShouldQueue
{
    public function handleTaskCompleted(TaskCompleted $event): void
    {
        $task = $event->task;
        $project = $task->project;

        if (! $project) {
            return;
        }

        $previousStatusId = $project->project_status_id;
        $project->refreshStatus();

        Log::info('Project status refreshed from TaskCompleted', [
            'project_id' => $project->id,
            'task_id' => $task->id,
            'previous_status_id' => $previousStatusId,
            'new_status_id' => $project->project_status_id,
        ]);
    }
}

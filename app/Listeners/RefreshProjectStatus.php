<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use Illuminate\Support\Facades\Log;

class RefreshProjectStatus
{
    public function handle(object $event): void
    {
        if (! $event instanceof TaskCompleted) {
            return;
        }

        $task = $event->task;
        $project = $task->project;

        if (! $project) {
            return;
        }

        $previousStatusId = $project->project_status_id;
        $updatedBy = $task->updated_by ?? $task->created_by;

        $project->refreshStatus($updatedBy);

        Log::info('Project status refreshed from TaskCompleted', [
            'project_id' => $project->id,
            'task_id' => $task->id,
            'previous_status_id' => $previousStatusId,
            'new_status_id' => $project->project_status_id,
        ]);
    }
}

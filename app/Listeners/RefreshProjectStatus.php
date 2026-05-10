<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Events\TaskUpdated;
use App\Models\Project;
use App\Models\ProjectHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class RefreshProjectStatus implements ShouldQueue
{
    public function handleTaskCompleted(TaskCompleted $event): void
    {
        $task = $event->task;
        $project = $task->project;

        $previousStatusId = $project->project_status_id;
        $project->refreshStatus();

        if ($previousStatusId !== $project->project_status_id) {
            $this->logStatusChange($project, $project->project_status_id, $event);
        }

        Log::info('Project status refreshed from TaskCompleted', [
            'project_id' => $project->id,
            'task_id' => $task->id,
            'new_status_id' => $project->project_status_id,
        ]);
    }

    public function handleTaskUpdated(TaskUpdated $event): void
    {
        $task = $event->task;
        $project = $task->project;

        $previousStatusId = $project->project_status_id;
        $project->refreshStatus();

        if ($previousStatusId !== $project->project_status_id) {
            $this->logStatusChange($project, $project->project_status_id, $event);
        }

        Log::info('Project status refreshed from TaskUpdated', [
            'project_id' => $project->id,
            'task_id' => $task->id,
            'new_status_id' => $project->project_status_id,
        ]);
    }

    private function logStatusChange(Project $project, int $statusId, $event): void
    {
        ProjectHistory::create([
            'project_id' => $project->id,
            'project_status_id' => $statusId,
            'created_by' => $project->created_by,
            'created_at' => now(),
        ]);
    }
}

<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Log;

class RefreshProjectStatus
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function handle(TaskCompleted $event): void
    {
        $task = $event->task();
        $project = $task->project;

        if (! $project) {
            return;
        }

        $previousStatusId = $project->project_status_id;
        $updatedBy = $task->updated_by ?? $task->created_by;

        $this->projectService->refreshStatus($project, $updatedBy);

        Log::info('Project status refreshed from TaskCompleted', [
            'project_id' => $project->id,
            'task_id' => $task->id,
            'previous_status_id' => $previousStatusId,
            'new_status_id' => $project->project_status_id,
        ]);
    }
}

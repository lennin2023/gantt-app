<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\ProjectHistory;

class ProjectObserver
{
    public function created(Project $project): void
    {
        $this->logStatusChange($project, $project->created_by);
    }

    public function updated(Project $project): void
    {
        if ($project->wasChanged('project_status_id')) {
            $this->logStatusChange($project, $project->updated_by ?? $project->created_by);
        }
    }

    private function logStatusChange(Project $project, ?int $userId): void
    {
        ProjectHistory::create([
            'project_id' => $project->id,
            'project_status_id' => $project->project_status_id,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }
}

<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\ProjectHistory;

class ProjectObserver
{
    public function updated(Project $project): void
    {
        if ($project->wasChanged('project_status_id')) {
            ProjectHistory::create([
                'project_id' => $project->id,
                'project_status_id' => $project->project_status_id,
                'created_by' => $project->updated_by,
                'created_at' => now(),
            ]);
        }
    }
}

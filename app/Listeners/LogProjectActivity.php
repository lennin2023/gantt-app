<?php

namespace App\Listeners;

use App\Events\ProjectCreated;
use App\Events\ProjectUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogProjectActivity implements ShouldQueue
{
    public function handle(ProjectCreated|ProjectUpdated $event): void
    {
        $action = match (true) {
            $event instanceof ProjectCreated => 'created',
            $event instanceof ProjectUpdated => 'updated',
        };

        Log::info("Project {$action}", [
            'project_id' => $event->project->id,
            'company_id' => $event->project->company_id,
            'name' => $event->project->name,
        ]);
    }
}

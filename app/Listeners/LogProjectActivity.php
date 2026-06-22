<?php

namespace App\Listeners;

use App\Events\ProjectCreated;
use App\Events\ProjectEvent;
use App\Events\ProjectUpdated;
use Illuminate\Support\Facades\Log;

class LogProjectActivity
{
    public function handle(ProjectEvent $event): void
    {
        $action = match ($event::class) {
            ProjectCreated::class => 'created',
            ProjectUpdated::class => 'updated',
            default => 'unknown',
        };

        $project = $event->project();

        Log::info("Project {$action}", [
            'project_id' => $project->id,
            'company_id' => $project->company_id,
            'name' => $project->name,
        ]);
    }
}

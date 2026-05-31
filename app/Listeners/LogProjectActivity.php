<?php

namespace App\Listeners;

use App\Events\ProjectCreated;
use App\Events\ProjectUpdated;
use Illuminate\Support\Facades\Log;

class LogProjectActivity
{
    public function handle(object $event): void
    {
        $action = match (true) {
            $event instanceof ProjectCreated => 'created',
            $event instanceof ProjectUpdated => 'updated',
            default => 'unknown',
        };

        Log::info("Project {$action}", [
            'project_id' => $event->project->id,
            'company_id' => $event->project->company_id,
            'name' => $event->project->name,
        ]);
    }
}

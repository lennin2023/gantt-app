<?php

namespace App\Listeners;

use App\Events\ProjectUserAssigned;
use App\Events\ProjectUserEvent;
use App\Events\ProjectUserRemoved;
use Illuminate\Support\Facades\Log;

class LogProjectUserActivity
{
    public function handle(ProjectUserEvent $event): void
    {
        $projectUser = $event->projectUser();

        $action = match ($event::class) {
            ProjectUserAssigned::class => 'Project user assigned',
            ProjectUserRemoved::class => 'Project user removed',
            default => 'Project user updated',
        };

        Log::info($action, [
            'project_id' => $projectUser->project_id,
            'user_id' => $projectUser->user_id,
            'project_role_id' => $projectUser->project_role_id,
        ]);
    }
}

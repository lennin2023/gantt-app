<?php

namespace App\Listeners;

use App\Events\ProjectUserAssigned;
use App\Events\ProjectUserRemoved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogProjectUserActivity implements ShouldQueue
{
    public function handle(ProjectUserAssigned|ProjectUserRemoved $event): void
    {
        if ($event instanceof ProjectUserAssigned) {
            Log::info('Project user assigned', [
                'project_id' => $event->projectUser->project_id,
                'user_id' => $event->projectUser->user_id,
                'project_role_id' => $event->projectUser->project_role_id,
            ]);
        }

        if ($event instanceof ProjectUserRemoved) {
            Log::info('Project user removed', [
                'project_id' => $event->projectId,
                'user_id' => $event->userId,
                'removed_by' => $event->removedBy,
            ]);
        }
    }
}

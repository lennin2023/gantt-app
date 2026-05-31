<?php

namespace App\Listeners;

use App\Events\ProjectUserAssigned;
use App\Events\ProjectUserRemoved;
use Illuminate\Support\Facades\Log;

class LogProjectUserActivity
{
    public function handle(object $event): void
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
                'project_id' => $event->projectUser->project_id,
                'user_id' => $event->projectUser->user_id,
            ]);
        }
    }
}

<?php

namespace App\Listeners;

use App\Events\MilestoneCreated;
use App\Events\MilestoneDeleted;
use App\Events\MilestoneRestored;
use App\Events\MilestoneUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogMilestoneActivity implements ShouldQueue
{
    public function handle(MilestoneCreated|MilestoneDeleted|MilestoneRestored|MilestoneUpdated $event): void
    {
        $action = match (true) {
            $event instanceof MilestoneCreated => 'created',
            $event instanceof MilestoneUpdated => 'updated',
            $event instanceof MilestoneDeleted => 'deleted',
            $event instanceof MilestoneRestored => 'restored',
        };

        Log::info("Milestone {$action}", [
            'milestone_id' => $event->milestone->id,
            'project_id' => $event->milestone->project_id,
            'name' => $event->milestone->name,
        ]);
    }
}

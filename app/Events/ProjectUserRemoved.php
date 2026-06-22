<?php

namespace App\Events;

use App\Models\ProjectUser;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectUserRemoved implements ProjectUserEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ProjectUser $projectUser,
    ) {}

    public function projectUser(): ProjectUser
    {
        return $this->projectUser;
    }
}

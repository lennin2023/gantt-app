<?php

namespace App\Events;

use App\Models\ProjectUser;

interface ProjectUserEvent
{
    public function projectUser(): ProjectUser;
}

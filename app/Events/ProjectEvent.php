<?php

namespace App\Events;

use App\Models\Project;

interface ProjectEvent
{
    public function project(): Project;
}

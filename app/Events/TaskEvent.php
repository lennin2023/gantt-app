<?php

namespace App\Events;

use App\Models\Task;

interface TaskEvent
{
    public function task(): Task;
}

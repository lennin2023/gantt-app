<?php

namespace App\Exceptions;

use App\Enums\TaskStatusEnum;

class TaskAlreadyInStatusException extends \RuntimeException
{
    public function __construct(TaskStatusEnum $status)
    {
        parent::__construct("La tarea ya se encuentra en estado {$status->label()}.");
    }
}

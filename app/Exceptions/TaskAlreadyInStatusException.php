<?php

namespace App\Exceptions;

use App\Enums\TaskStatusEnum;

class TaskAlreadyInStatusException extends \RuntimeException
{
    public function __construct(TaskStatusEnum $status)
    {
        parent::__construct(__('exceptions.task.already_in_status', ['status' => $status->label()]));
    }
}

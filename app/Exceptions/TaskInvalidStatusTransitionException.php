<?php

namespace App\Exceptions;

use App\Enums\TaskStatusEnum;

class TaskInvalidStatusTransitionException extends \RuntimeException
{
    public function __construct(TaskStatusEnum $from, TaskStatusEnum $to)
    {
        parent::__construct(__('exceptions.task.invalid_status_transition', [
            'from' => $from->label(),
            'to' => $to->label(),
        ]));
    }
}

<?php

namespace App\Exceptions;

class TaskAssignmentNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.task_assignment.not_found'));
    }
}

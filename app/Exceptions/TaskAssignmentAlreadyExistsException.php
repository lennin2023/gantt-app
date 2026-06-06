<?php

namespace App\Exceptions;

class TaskAssignmentAlreadyExistsException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.task_assignment.already_exists'));
    }
}

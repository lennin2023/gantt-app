<?php

namespace App\Exceptions;

class TaskNotDeletedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.task.not_deleted'));
    }
}

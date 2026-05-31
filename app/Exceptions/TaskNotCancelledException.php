<?php

namespace App\Exceptions;

class TaskNotCancelledException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.task.not_cancelled'));
    }
}

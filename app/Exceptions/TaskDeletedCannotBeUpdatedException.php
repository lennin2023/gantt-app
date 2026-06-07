<?php

namespace App\Exceptions;

class TaskDeletedCannotBeUpdatedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.task.deleted_cannot_be_updated'));
    }
}

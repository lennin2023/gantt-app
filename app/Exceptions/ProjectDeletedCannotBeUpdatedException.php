<?php

namespace App\Exceptions;

class ProjectDeletedCannotBeUpdatedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.project.deleted_cannot_be_updated'));
    }
}

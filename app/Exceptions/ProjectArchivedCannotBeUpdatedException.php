<?php

namespace App\Exceptions;

class ProjectArchivedCannotBeUpdatedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.project.archived_cannot_be_updated'));
    }
}

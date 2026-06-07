<?php

namespace App\Exceptions;

class ProjectNotDeletedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.project.not_deleted'));
    }
}

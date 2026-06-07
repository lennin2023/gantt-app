<?php

namespace App\Exceptions;

class ProjectNotArchivedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.project.not_archived'));
    }
}

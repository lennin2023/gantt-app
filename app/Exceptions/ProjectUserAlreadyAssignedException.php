<?php

namespace App\Exceptions;

class ProjectUserAlreadyAssignedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.project_user.already_assigned'));
    }
}

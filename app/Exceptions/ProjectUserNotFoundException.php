<?php

namespace App\Exceptions;

class ProjectUserNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.project_user.not_found'));
    }
}

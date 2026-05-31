<?php

namespace App\Exceptions;

use App\Enums\ProjectStatusEnum;

class ProjectAlreadyInStatusException extends \RuntimeException
{
    public function __construct(ProjectStatusEnum $status)
    {
        parent::__construct(__('exceptions.project.already_in_status', ['status' => $status->label()]));
    }
}

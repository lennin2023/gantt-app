<?php

namespace App\Exceptions;

use App\Enums\ProjectStatusEnum;

class ProjectAlreadyInStatusException extends \RuntimeException
{
    public function __construct(ProjectStatusEnum $status)
    {
        parent::__construct("El proyecto ya se encuentra en estado {$status->label()}.");
    }
}

<?php

namespace App\Exceptions;

use App\Enums\ProjectStatusEnum;

class ProjectInvalidStatusTransitionException extends \RuntimeException
{
    public function __construct(ProjectStatusEnum $from, ProjectStatusEnum $to)
    {
        parent::__construct(__('exceptions.project.invalid_status_transition', [
            'from' => $from->label(),
            'to' => $to->label(),
        ]));
    }
}

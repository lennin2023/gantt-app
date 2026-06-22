<?php

namespace App\Exceptions;

use Exception;

class CycleDetectionException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('exceptions.task.cycle_detected'));
    }
}

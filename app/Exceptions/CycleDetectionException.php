<?php

namespace App\Exceptions;

use Exception;

class CycleDetectionException extends Exception
{
    public function __construct(string $message = 'Adding this dependency would create a cycle')
    {
        parent::__construct($message);
    }
}

<?php

namespace App\Exceptions;

class ProjectUserAlreadyAssignedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('El usuario ya está asignado a este proyecto.');
    }
}

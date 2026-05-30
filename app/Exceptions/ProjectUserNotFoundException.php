<?php

namespace App\Exceptions;

class ProjectUserNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('El usuario no está asignado a este proyecto.');
    }
}

<?php

namespace App\Enums;

enum TaskTypeEnum: string
{
    case CONTAINER = 'container';
    case TASK = 'task';
    case MILESTONE = 'milestone';

    public function label(): string
    {
        return match ($this) {
            self::CONTAINER => 'Contenedor',
            self::TASK => 'Tarea',
            self::MILESTONE => 'Hito',
        };
    }
}

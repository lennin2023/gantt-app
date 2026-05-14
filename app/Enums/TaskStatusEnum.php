<?php

namespace App\Enums;

enum TaskStatusEnum: int
{
    case PENDING = 1;
    case IN_PROGRESS = 2;
    case COMPLETED = 3;
    case DELAYED = 4;

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En Progreso',
            self::COMPLETED => 'Completada',
            self::DELAYED => 'Atrasada',
        };
    }

    public function slug(): string
    {
        return strtolower($this->name);
    }
}

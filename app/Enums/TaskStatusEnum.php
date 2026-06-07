<?php

namespace App\Enums;

enum TaskStatusEnum: int
{
    case PENDING = 1;
    case IN_PROGRESS = 2;
    case COMPLETED = 3;
    case ON_HOLD = 4;
    case CANCELLED = 5;
    case DELETED = 6;

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En Progreso',
            self::COMPLETED => 'Completada',
            self::ON_HOLD => 'En Pausa',
            self::CANCELLED => 'Cancelada',
            self::DELETED => 'Eliminada',
        };
    }

    public function slug(): string
    {
        return strtolower($this->name);
    }
}

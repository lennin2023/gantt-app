<?php

namespace App\Enums;

enum ProjectStatusEnum: int
{
    case ACTIVE = 1;
    case COMPLETED = 2;
    case ON_HOLD = 3;
    case CANCELLED = 4;
    case DELETED = 5;

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::COMPLETED => 'Completado',
            self::ON_HOLD => 'En Pausa',
            self::CANCELLED => 'Cancelado',
            self::DELETED => 'Eliminado',
        };
    }

    public function slug(): string
    {
        return strtolower($this->name);
    }
}

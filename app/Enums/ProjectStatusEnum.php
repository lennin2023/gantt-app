<?php

namespace App\Enums;

enum ProjectStatusEnum: int
{
    case ACTIVE = 1;
    case COMPLETED = 2;
    case ARCHIVED = 3;
    case ON_HOLD = 4;
    case CANCELLED = 5;

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::COMPLETED => 'Completado',
            self::ARCHIVED => 'Archivado',
            self::ON_HOLD => 'En Pausa',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function slug(): string
    {
        return strtolower($this->name);
    }
}

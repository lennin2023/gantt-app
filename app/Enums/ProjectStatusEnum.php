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

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => '#22c55e',
            self::COMPLETED => '#3b82f6',
            self::ARCHIVED => '#6b7280',
            self::ON_HOLD => '#f59e0b',
            self::CANCELLED => '#ef4444',
        };
    }

    public function name(): string
    {
        return match ($this) {
            self::ACTIVE => 'active',
            self::COMPLETED => 'completed',
            self::ARCHIVED => 'archived',
            self::ON_HOLD => 'on_hold',
            self::CANCELLED => 'cancelled',
        };
    }
}

<?php

namespace App\Enums;

enum ProjectStatusEnum: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case ARCHIVED = 'archived';
    case ON_HOLD = 'on_hold';
    case CANCELLED = 'cancelled';

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
}

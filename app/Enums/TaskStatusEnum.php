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

    /* public function color(): string
    {
        return match ($this) {
            self::PENDING => '#6b7280',
            self::IN_PROGRESS => '#3b82f6',
            self::COMPLETED => '#22c55e',
            self::DELAYED => '#ef4444',
        };
    } */

    public function name(): string
    {
        return match ($this) {
            self::PENDING => 'pending',
            self::IN_PROGRESS => 'in_progress',
            self::COMPLETED => 'completed',
            self::DELAYED => 'delayed',
        };
    }
}

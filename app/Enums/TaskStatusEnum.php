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
        return __('enums.task_status.'.$this->name);
    }

    public function slug(): string
    {
        return strtolower($this->name);
    }
}

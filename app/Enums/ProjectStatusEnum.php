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
        return __('enums.project_status.'.$this->name);
    }

    public function slug(): string
    {
        return strtolower($this->name);
    }
}

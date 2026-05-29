<?php

namespace App\Enums;

enum ProjectRoleEnum: int
{
    case PROJECT_MANAGER = 1;
    case TEAM_MEMBER = 2;
    case VIEWER = 3;

    public function label(): string
    {
        return match ($this) {
            self::PROJECT_MANAGER => 'Project Manager',
            self::TEAM_MEMBER => 'Team Member',
            self::VIEWER => 'Viewer',
        };
    }

    public function slug(): string
    {
        return strtolower($this->name);
    }
}

<?php

namespace App\Enums;

enum ProjectRoleEnum: int
{
    case PROJECT_MANAGER = 1;
    case DEVELOPER = 2;
    case ANALYST = 3;
    case DESIGNER = 4;
    case TESTER = 5;
    case VIEWER = 6;

    public const MANAGER_LEVEL = 3;

    public const EXECUTOR_LEVEL = 2;

    public const SPECTATOR_LEVEL = 1;

    public function label(): string
    {
        return match ($this) {
            self::PROJECT_MANAGER => 'Project Manager',
            self::DEVELOPER => 'Developer',
            self::ANALYST => 'Analyst',
            self::DESIGNER => 'Designer',
            self::TESTER => 'Tester',
            self::VIEWER => 'Viewer',
        };
    }

    public function slug(): string
    {
        return strtolower($this->name);
    }

    public function level(): int
    {
        return match ($this) {
            self::PROJECT_MANAGER => self::MANAGER_LEVEL,
            self::DEVELOPER,
            self::ANALYST,
            self::DESIGNER,
            self::TESTER => self::EXECUTOR_LEVEL,
            self::VIEWER => self::SPECTATOR_LEVEL,
        };
    }
}

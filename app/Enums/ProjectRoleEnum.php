<?php

namespace App\Enums;

enum ProjectRoleEnum: int
{
    case MANAGER = 1;
    case PROJECT_MANAGER = 2;
    case DEVELOPER = 3;
    case ANALYST = 4;
    case DESIGNER = 5;
    case TESTER = 6;
    case VIEWER = 7;

    public const MIN_LEVEL_CREATE_TASKS = 3;

    public const MIN_LEVEL_MANAGE_PROJECT = 4;

    public function label(): string
    {
        return match ($this) {
            self::MANAGER => 'Manager',
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
            self::MANAGER => 4,
            self::PROJECT_MANAGER => 3,
            self::DEVELOPER => 2,
            self::ANALYST => 2,
            self::DESIGNER => 2,
            self::TESTER => 2,
            self::VIEWER => 1,
        };
    }

    public function canCreateTasks(): bool
    {
        return $this->level() >= self::MIN_LEVEL_CREATE_TASKS;
    }

    public function canAssignTasks(): bool
    {
        return $this->level() >= self::MIN_LEVEL_CREATE_TASKS;
    }

    public function canManageProject(): bool
    {
        return $this->level() >= self::MIN_LEVEL_MANAGE_PROJECT;
    }

    public function canViewProject(): bool
    {
        return $this->level() >= 1;
    }
}

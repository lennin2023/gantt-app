<?php

namespace App\Enums;

enum RoleEnum: int
{
    case ADMIN = 1;
    case COMPANY_OWNER = 2;
    case PROJECT_MANAGER = 3;
    case DEVELOPER = 4;
    case VIEWER = 5;

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::COMPANY_OWNER => 'Company Owner',
            self::PROJECT_MANAGER => 'Project Manager',
            self::DEVELOPER => 'Developer',
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
            self::ADMIN => 5,
            self::COMPANY_OWNER => 4,
            self::PROJECT_MANAGER => 3,
            self::DEVELOPER => 2,
            self::VIEWER => 1,
        };
    }

    public function canManageProject(): bool
    {
        return $this->level() >= self::PROJECT_MANAGER->level();
    }

    public function canManageTasks(): bool
    {
        return $this->level() >= self::DEVELOPER->level();
    }
}

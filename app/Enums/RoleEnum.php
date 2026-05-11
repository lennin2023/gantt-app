<?php

namespace App\Enums;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case COMPANY_OWNER = 'company_owner';
    case PROJECT_MANAGER = 'project_manager';
    case DEVELOPER = 'developer';
    case VIEWER = 'viewer';

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

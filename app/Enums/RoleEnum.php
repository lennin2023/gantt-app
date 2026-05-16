<?php

namespace App\Enums;

enum RoleEnum: int
{
    case SUPER_ADMIN = 1;
    case SUPERVISOR = 2;
    case GESTOR = 3;
    case PROJECT_MANAGER = 4;
    case TEAM_MEMBER = 5;
    case VIEWER = 6;

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::SUPERVISOR => 'Supervisor',
            self::GESTOR => 'Gestor',
            self::PROJECT_MANAGER => 'Project Manager',
            self::TEAM_MEMBER => 'Team Member',
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
            self::SUPER_ADMIN => 6,
            self::SUPERVISOR => 5,
            self::GESTOR => 4,
            self::PROJECT_MANAGER => 3,
            self::TEAM_MEMBER => 2,
            self::VIEWER => 1,
        };
    }

    public function canViewAllCompanies(): bool
    {
        return $this->level() >= self::SUPERVISOR->level();
    }

    public function canManageProjects(): bool
    {
        return $this->level() >= self::GESTOR->level();
    }

    public function canManageTasks(): bool
    {
        return $this->level() >= self::PROJECT_MANAGER->level();
    }
}

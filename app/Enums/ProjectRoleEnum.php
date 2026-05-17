<?php

namespace App\Enums;

enum ProjectRoleEnum: int
{
    case GESTOR = 1;
    case PM = 2;
    case DEV = 3;
    case ANALISTA = 4;
    case DISEÑADOR = 5;
    case TESTER = 6;
    case ESPECTADOR = 7;

    public const MIN_LEVEL_CREATE_TASKS = 3;

    public const MIN_LEVEL_MANAGE_PROJECT = 4;

    public function label(): string
    {
        return match ($this) {
            self::GESTOR => 'Gestor',
            self::PM => 'PM',
            self::DEV => 'Dev',
            self::ANALISTA => 'Analista',
            self::DISEÑADOR => 'Diseñador',
            self::TESTER => 'Tester',
            self::ESPECTADOR => 'Espectador',
        };
    }

    public function slug(): string
    {
        return strtolower(match ($this) {
            self::GESTOR => 'gestor',
            self::PM => 'pm',
            self::DEV => 'dev',
            self::ANALISTA => 'analista',
            self::DISEÑADOR => 'disenador',
            self::TESTER => 'tester',
            self::ESPECTADOR => 'espectador',
        });
    }

    public function level(): int
    {
        return match ($this) {
            self::GESTOR => 4,
            self::PM => 3,
            self::DEV => 2,
            self::ANALISTA => 2,
            self::DISEÑADOR => 2,
            self::TESTER => 2,
            self::ESPECTADOR => 1,
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

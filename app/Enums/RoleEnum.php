<?php

namespace App\Enums;

enum RoleEnum: int
{
    case SUPER_ADMIN = 1;
    case ADMIN = 2;
    case STAFF = 3;

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::STAFF => 'Staff',
        };
    }

    public function slug(): string
    {
        return strtolower($this->name);
    }
}

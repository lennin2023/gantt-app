<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === RoleEnum::SUPER_ADMIN->slug();
    }

    public function isAdmin(): bool
    {
        return $this->slug === RoleEnum::ADMIN->slug();
    }

    public function isStaff(): bool
    {
        return $this->slug === RoleEnum::STAFF->slug();
    }
}

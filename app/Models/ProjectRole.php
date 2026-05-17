<?php

namespace App\Models;

use App\Enums\ProjectRoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRole extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'level'];

    public function projectUsers(): HasMany
    {
        return $this->hasMany(ProjectUser::class);
    }

    public function isGestor(): bool
    {
        return $this->slug === ProjectRoleEnum::GESTOR->slug();
    }

    public function isPm(): bool
    {
        return $this->slug === ProjectRoleEnum::PM->slug();
    }

    public function isDev(): bool
    {
        return $this->slug === ProjectRoleEnum::DEV->slug();
    }

    public function isAnalista(): bool
    {
        return $this->slug === ProjectRoleEnum::ANALISTA->slug();
    }

    public function isDiseñador(): bool
    {
        return $this->slug === ProjectRoleEnum::DISEÑADOR->slug();
    }

    public function isTester(): bool
    {
        return $this->slug === ProjectRoleEnum::TESTER->slug();
    }

    public function isEspectador(): bool
    {
        return $this->slug === ProjectRoleEnum::ESPECTADOR->slug();
    }
}

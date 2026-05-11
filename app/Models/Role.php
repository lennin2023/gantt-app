<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'level'];

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->slug === 'admin';
    }

    public function isCompanyOwner(): bool
    {
        return $this->slug === 'company_owner';
    }

    public function isProjectManager(): bool
    {
        return $this->slug === 'project_manager';
    }

    public function isDeveloper(): bool
    {
        return $this->slug === 'developer';
    }
}

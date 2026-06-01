<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectStatus extends Model
{
    use HasCreatedBy;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProjectHistory::class);
    }
}

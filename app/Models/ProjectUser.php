<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUser extends Model
{
    use HasCreatedBy, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'user_id',
        'project_role_id',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projectRole(): BelongsTo
    {
        return $this->belongsTo(ProjectRole::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignment extends Model
{
    protected $fillable = [
        'task_id',
        'project_user_id',
        'task_role_id',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function projectUser(): BelongsTo
    {
        return $this->belongsTo(ProjectUser::class);
    }

    public function taskRole(): BelongsTo
    {
        return $this->belongsTo(TaskRole::class);
    }
}

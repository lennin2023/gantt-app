<?php

namespace App\Models;

use App\Enums\TaskStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_user_id',
        'task_status_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'progress',
        'order',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'task_status_id' => TaskStatusEnum::PENDING->value,
        'progress' => 0,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'progress' => 'integer',
        ];
    }

    public function projectUser(): BelongsTo
    {
        return $this->belongsTo(ProjectUser::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id'
        );
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id'
        );
    }

    public function isPending(): bool
    {
        return $this->task_status_id === TaskStatusEnum::PENDING->value;
    }

    public function isInProgress(): bool
    {
        return $this->task_status_id === TaskStatusEnum::IN_PROGRESS->value;
    }

    public function isCompleted(): bool
    {
        return $this->task_status_id === TaskStatusEnum::COMPLETED->value;
    }

    public function isDelayed(): bool
    {
        return $this->task_status_id === TaskStatusEnum::DELAYED->value;
    }
}

<?php

namespace App\Models;

use App\Enums\TaskStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'parent_id',
        'task_status_id',
        'title',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('order');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
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
        )->withPivot('type');
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id'
        )->withPivot('type');
    }

    public function isLeaf(): bool
    {
        return $this->children()->doesntExist();
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

    public function isCancelled(): bool
    {
        return $this->task_status_id === TaskStatusEnum::CANCELLED->value;
    }
}

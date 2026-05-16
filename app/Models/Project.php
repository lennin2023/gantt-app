<?php

namespace App\Models;

use App\Enums\ProjectStatusEnum;
use App\Enums\TaskStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'project_status_id',
        'name',
        'description',
        'color',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'project_status_id' => ProjectStatusEnum::ACTIVE->value,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'project_status_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('order');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('date');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProjectHistory::class)->orderByDesc('created_at');
    }

    public function getStats(): array
    {
        $total = $this->tasks()->count();
        $completed = $this->tasks()->where('task_status_id', TaskStatusEnum::COMPLETED->value)->count();
        $avgProgress = $this->tasks()->avg('progress');

        return [
            'total_tasks' => $total,
            'completed_tasks' => $completed,
            'overall_progress' => $total > 0 ? (int) $avgProgress : 0,
        ];
    }

    public function isAllTasksCompleted(): bool
    {
        $total = $this->tasks()->count();

        if ($total === 0) {
            return false;
        }

        $completed = $this->tasks()->where('task_status_id', TaskStatusEnum::COMPLETED->value)->count();

        return $total === $completed;
    }

    public function refreshStatus(): void
    {
        $this->project_status_id = $this->isAllTasksCompleted()
            ? ProjectStatusEnum::COMPLETED->value
            : ProjectStatusEnum::ACTIVE->value;

        $this->save();
    }
}

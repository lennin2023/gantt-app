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

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('date');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProjectHistory::class)->orderByDesc('created_at');
    }

    public function projectUsers(): HasMany
    {
        return $this->hasMany(ProjectUser::class);
    }

    public function getStats(): array
    {
        $stats = Task::whereHas('projectUser', fn ($q) => $q->where('project_id', $this->id))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN task_status_id = ? THEN 1 ELSE 0 END) as completed,
                AVG(progress) as avg_progress
            ', [TaskStatusEnum::COMPLETED->value])
            ->first();

        return [
            'total_tasks' => (int) $stats->total,
            'completed_tasks' => (int) $stats->completed,
            'overall_progress' => $stats->total > 0 ? (int) $stats->avg_progress : 0,
        ];
    }

    public function isAllTasksCompleted(): bool
    {
        $result = Task::whereHas('projectUser', fn ($q) => $q->where('project_id', $this->id))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN task_status_id = ? THEN 1 ELSE 0 END) as completed
            ', [TaskStatusEnum::COMPLETED->value]
            )
            ->first();

        if (! $result || (int) $result->total === 0) {
            return false;
        }

        return (int) $result->total === (int) $result->completed;
    }

    public function refreshStatus(): void
    {
        $this->project_status_id = $this->isAllTasksCompleted()
            ? ProjectStatusEnum::COMPLETED->value
            : ProjectStatusEnum::ACTIVE->value;

        $this->save();
    }
}

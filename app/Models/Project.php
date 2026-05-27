<?php

namespace App\Models;

use App\Enums\ProjectStatusEnum;
use App\Enums\TaskStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

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

        $total = (int) $stats?->total;
        $completed = (int) $stats?->completed;
        $avgProgress = (int) $stats?->avg_progress;

        return [
            'total_tasks' => $total,
            'completed_tasks' => $completed,
            'overall_progress' => $total > 0 ? $avgProgress : 0,
        ];
    }

    public function isAllTasksCompleted(): bool
    {
        $stats = $this->getStats();

        if ($stats['total_tasks'] === 0) {
            return false;
        }

        return $stats['total_tasks'] === $stats['completed_tasks'];
    }

    public function refreshStatus(): void
    {
        $protectedStatuses = [
            ProjectStatusEnum::ON_HOLD->value,
            ProjectStatusEnum::ARCHIVED->value,
            ProjectStatusEnum::CANCELLED->value,
        ];

        if (in_array($this->project_status_id, $protectedStatuses)) {
            return;
        }

        $this->project_status_id = $this->isAllTasksCompleted()
            ? ProjectStatusEnum::COMPLETED->value
            : ProjectStatusEnum::ACTIVE->value;

        $this->save();
    }
}

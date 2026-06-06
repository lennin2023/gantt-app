<?php

namespace App\Repositories\Eloquent;

use App\Enums\TaskStatusEnum;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\Task;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function getAllByUser(int $userId, int $perPage = 10, ?int $statusId = null): LengthAwarePaginator
    {
        return Project::with('status')
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->orWhereIn('id', ProjectUser::where('user_id', $userId)->select('project_id'));
            })
            ->when($statusId, fn ($q) => $q->where('project_status_id', $statusId))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findById(int $id, array $with = []): ?Project
    {
        return Project::with($with)->find($id);
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh();
    }

    public function getStats(int $projectId): array
    {
        $stats = Task::where('project_id', $projectId)
            ->whereDoesntHave('children')
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
}

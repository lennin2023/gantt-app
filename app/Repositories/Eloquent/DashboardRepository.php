<?php

namespace App\Repositories\Eloquent;

use App\Enums\ProjectStatusEnum;
use App\Models\Project;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function getMetrics(int $userId): array
    {
        $result = Project::where('created_by', $userId)
            ->selectRaw('
                COUNT(*) as total_projects,
                SUM(CASE WHEN project_status_id = ? THEN 1 ELSE 0 END) as active_projects,
                SUM(CASE WHEN project_status_id = ? THEN 1 ELSE 0 END) as completed_projects
            ', [
                ProjectStatusEnum::ACTIVE->value,
                ProjectStatusEnum::COMPLETED->value,
            ])
            ->first();

        $overallProgress = Project::where('projects.created_by', $userId)
            ->join('tasks', function ($join) {
                $join->on('projects.id', '=', 'tasks.project_id')
                    ->whereNull('tasks.deleted_at');
            })
            ->avg('tasks.progress');

        return [
            'total_projects' => (int) $result->total_projects,
            'active_projects' => (int) $result->active_projects,
            'completed_projects' => (int) $result->completed_projects,
            'overall_progress' => (int) round($overallProgress ?? 0),
        ];
    }

    public function getProjects(int $userId, int $limit = 10): array
    {
        return Project::select([
            'projects.id',
            'projects.name',
            'projects.color',
            'project_statuses.name as status_name',
            'project_statuses.color as status_color',
            DB::raw('COALESCE(ROUND(AVG(tasks.progress)), 0) as progress'),
            DB::raw('COUNT(tasks.id) as total_tasks'),
        ])
            ->join('project_statuses', 'projects.project_status_id', '=', 'project_statuses.id')
            ->leftJoin('tasks', function ($join) {
                $join->on('projects.id', '=', 'tasks.project_id')
                    ->whereNull('tasks.deleted_at');
            })
            ->where('projects.created_by', $userId)
            ->whereNull('projects.deleted_at')
            ->groupBy(
                'projects.id',
                'projects.name',
                'projects.color',
                'project_statuses.name',
                'project_statuses.color',
            )
            ->orderByDesc('projects.created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color,
                'status_name' => $project->status_name,
                'status_color' => $project->status_color,
                'progress' => (int) $project->progress,
                'total_tasks' => (int) $project->total_tasks,
            ])
            ->toArray();
    }
}

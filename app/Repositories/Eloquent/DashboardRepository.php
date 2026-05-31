<?php

namespace App\Repositories\Eloquent;

use App\Enums\ProjectStatusEnum;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function getMetrics(int $userId): array
    {
        $result = Project::leftJoin('tasks', 'projects.id', '=', 'tasks.project_id')
            ->where($this->userProjectScope($userId))
            ->selectRaw('
                COUNT(DISTINCT projects.id) AS total_projects,
                COUNT(DISTINCT CASE WHEN projects.project_status_id = ? THEN projects.id END) AS active_projects,
                COUNT(DISTINCT CASE WHEN projects.project_status_id = ? THEN projects.id END) AS completed_projects,
                COALESCE(ROUND(AVG(tasks.progress)), 0) AS overall_progress
            ', [
                ProjectStatusEnum::ACTIVE->value,
                ProjectStatusEnum::COMPLETED->value,
            ])
            ->first();

        return [
            'total_projects' => (int) $result->total_projects,
            'active_projects' => (int) $result->active_projects,
            'completed_projects' => (int) $result->completed_projects,
            'overall_progress' => (int) $result->overall_progress,
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
            ->leftJoin('tasks', 'projects.id', '=', 'tasks.project_id')
            ->where($this->userProjectScope($userId))
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

    private function userProjectScope(int $userId): \Closure
    {
        $memberOfIds = ProjectUser::where('user_id', $userId)->select('project_id');

        return fn ($query) => $query
            ->where('projects.created_by', $userId)
            ->orWhereIn('projects.id', $memberOfIds);
    }
}

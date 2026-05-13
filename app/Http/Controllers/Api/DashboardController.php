<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProjectStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $userId = Auth::id();

        return response()->json([
            'metrics' => $this->getMetrics($userId),
            'projects' => $this->getProjects($userId),
        ]);
    }

    private function getMetrics(int $userId): array
    {
        $projects = Project::where('created_by', $userId);

        $totalProjects = (clone $projects)->count();

        $activeProjects = (clone $projects)
            ->where('project_status_id', ProjectStatusEnum::ACTIVE->value)
            ->count();

        $completedProjects = (clone $projects)
            ->where('project_status_id', ProjectStatusEnum::COMPLETED->value)
            ->count();

        $overallProgress = (clone $projects)
            ->join('tasks', function ($join) {
                $join->on('projects.id', '=', 'tasks.project_id')
                    ->whereNull('tasks.deleted_at');
            })
            ->avg('tasks.progress');

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'overall_progress' => (int) round($overallProgress ?? 0),
        ];
    }

    private function getProjects(int $userId): array
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
            ->limit(10)
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

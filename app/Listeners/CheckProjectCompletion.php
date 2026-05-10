<?php

namespace App\Listeners;

use App\Enums\TaskStatus;
use App\Events\TaskCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CheckProjectCompletion implements ShouldQueue
{
    public function handle(TaskCompleted $event): void
    {
        $project = $event->task->project;

        $totalTasks = $project->tasks()->count();
        $completedTasks = $project->tasks()
            ->where('status', TaskStatus::COMPLETED)
            ->count();

        if ($totalTasks > 0 && $totalTasks === $completedTasks) {
            Log::info('All tasks completed in project', [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'total_tasks' => $totalTasks,
            ]);
        }
    }
}

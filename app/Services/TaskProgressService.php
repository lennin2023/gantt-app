<?php

namespace App\Services;

use App\Enums\TaskStatusEnum;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Support\Collection;

class TaskProgressService
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function recalculateAncestors(Task $task): void
    {
        $ancestorPaths = $this->getAncestorPaths($task);

        if ($ancestorPaths->isEmpty()) {
            return;
        }

        $ancestors = Task::whereIn('path', $ancestorPaths)
            ->where('type', 'container')
            ->where('task_status_id', '!=', TaskStatusEnum::DELETED->value)
            ->orderBy('path', 'asc')
            ->get();

        $lastProcessed = null;

        foreach ($ancestors->reverse() as $ancestor) {
            $changed = $this->recalculate($ancestor);
            $lastProcessed = $ancestor;

            if (! $changed) {
                break;
            }
        }

        if ($lastProcessed && ! $lastProcessed->parent_id) {
            $this->projectService->refreshDates($task->project_id);
        }
    }

    /**
     * Extracts all ancestor paths from the task's materialized path.
     *
     * For path "0001/0002/0003" returns ["0001", "0001/0002"].
     */
    private function getAncestorPaths(Task $task): Collection
    {
        if (! $task->path || ! str_contains($task->path, '/')) {
            return collect();
        }

        $segments = explode('/', $task->path);
        $paths = collect();
        $current = '';

        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                break;
            }

            $current = $current === '' ? $segment : "{$current}/{$segment}";
            $paths->push($current);
        }

        return $paths;
    }

    public function recalculate(Task $parent): bool
    {
        if (! $parent->isContainer()) {
            return false;
        }

        if ($parent->task_status_id === TaskStatusEnum::DELETED->value) {
            return false;
        }

        $children = $this->taskRepository->getChildrenForProgressCalc($parent->id);

        if ($children->isEmpty()) {
            return $this->clear($parent);
        }

        $newProgress = (int) round($children->avg('progress'));
        $newStatus = $this->calculateStatus($children);
        $newStartDate = $this->calculateStartDate($children);
        $newEndDate = $this->calculateEndDate($children);

        $changed = $parent->progress !== $newProgress
            || $parent->task_status_id !== $newStatus
            || $parent->start_date?->toDateString() !== $newStartDate
            || $parent->end_date?->toDateString() !== $newEndDate;

        if ($changed) {
            $parent->progress = $newProgress;
            $parent->task_status_id = $newStatus;
            $parent->start_date = $newStartDate;
            $parent->end_date = $newEndDate;
            $parent->saveQuietly();
        }

        return $changed;
    }

    private function clear(Task $parent): bool
    {
        $changed = $parent->progress !== 0
            || $parent->task_status_id !== TaskStatusEnum::PENDING->value
            || $parent->start_date !== null
            || $parent->end_date !== null;

        if ($changed) {
            $parent->progress = 0;
            $parent->task_status_id = TaskStatusEnum::PENDING->value;
            $parent->start_date = null;
            $parent->end_date = null;
            $parent->saveQuietly();
        }

        return $changed;
    }

    private function calculateStatus(Collection $children): int
    {
        if ($children->every(fn ($c) => in_array($c->task_status_id, [
            TaskStatusEnum::CANCELLED->value,
            TaskStatusEnum::DELETED->value,
        ]))) {
            return TaskStatusEnum::CANCELLED->value;
        }

        $active = $children->filter(fn ($c) => ! in_array($c->task_status_id, [
            TaskStatusEnum::CANCELLED->value,
            TaskStatusEnum::DELETED->value,
        ]));

        if ($active->every(fn ($c) => $c->task_status_id === TaskStatusEnum::COMPLETED->value)) {
            return TaskStatusEnum::COMPLETED->value;
        }

        if ($active->contains(fn ($c) => in_array($c->task_status_id, [
            TaskStatusEnum::IN_PROGRESS->value,
            TaskStatusEnum::ON_HOLD->value,
        ]))) {
            return TaskStatusEnum::IN_PROGRESS->value;
        }

        if ($active->contains(fn ($c) => $c->task_status_id === TaskStatusEnum::COMPLETED->value)) {
            return TaskStatusEnum::IN_PROGRESS->value;
        }

        return TaskStatusEnum::PENDING->value;
    }

    private function calculateStartDate(Collection $children): ?string
    {
        return $children
            ->filter(fn ($c) => $c->start_date !== null)
            ->min(fn ($c) => $c->start_date?->toDateString());
    }

    private function calculateEndDate(Collection $children): ?string
    {
        return $children
            ->filter(fn ($c) => $c->end_date !== null)
            ->max(fn ($c) => $c->end_date?->toDateString());
    }
}

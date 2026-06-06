<?php

namespace App\Services;

use App\Enums\TaskStatusEnum;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Support\Collection;

class TaskProgressService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function recalculateAncestors(Task $task): void
    {
        $parent = Task::find($task->parent_id);

        while ($parent) {
            $changed = $this->recalculate($parent);

            if (! $changed) {
                break;
            }

            $parent = $parent->parent_id ? Task::find($parent->parent_id) : null;
        }
    }

    private function recalculate(Task $parent): bool
    {
        $children = Task::where('parent_id', $parent->id)
            ->where('task_status_id', '!=', TaskStatusEnum::CANCELLED->value)
            ->get();

        if ($children->isEmpty()) {
            return false;
        }

        $newProgress = $this->taskRepository->getLeafTasksAvgProgress($parent->id);
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

    private function calculateStatus(Collection $children): int
    {
        // Todos cancelados
        if ($children->every(fn ($c) => $c->task_status_id === TaskStatusEnum::CANCELLED->value)) {
            return TaskStatusEnum::CANCELLED->value;
        }

        $nonCancelled = $children->filter(
            fn ($c) => $c->task_status_id !== TaskStatusEnum::CANCELLED->value
        );

        // Todos completados
        if ($nonCancelled->every(fn ($c) => $c->task_status_id === TaskStatusEnum::COMPLETED->value)) {
            return TaskStatusEnum::COMPLETED->value;
        }

        // Alguno en progreso o delayed
        if ($nonCancelled->contains(fn ($c) => in_array($c->task_status_id, [
            TaskStatusEnum::IN_PROGRESS->value,
            TaskStatusEnum::DELAYED->value,
        ]))) {
            return TaskStatusEnum::IN_PROGRESS->value;
        }

        // Alguno completado pero no todos
        if ($nonCancelled->contains(fn ($c) => $c->task_status_id === TaskStatusEnum::COMPLETED->value)) {
            return TaskStatusEnum::IN_PROGRESS->value;
        }

        // Todos pendientes
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

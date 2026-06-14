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
        $ancestorIds = $task->getAncestorIds();

        if (empty($ancestorIds)) {
            return;
        }

        // Recalcular de más cercano a más lejano
        $ancestors = Task::whereIn('id', $ancestorIds)
            ->orderByDesc('path')
            ->get();

        foreach ($ancestors as $ancestor) {
            $changed = $this->recalculate($ancestor);

            if (! $changed) {
                break;
            }
        }
    }

    private function recalculate(Task $parent): bool
    {
        if (! $parent->isContainer()) {
            return false;
        }

        $children = Task::where('parent_id', $parent->id)
            ->whereNotIn('task_status_id', [
                TaskStatusEnum::CANCELLED->value,
                TaskStatusEnum::DELETED->value,
            ])
            ->get();

        if ($children->isEmpty()) {
            return false;
        }

        $newProgress = $this->taskRepository->getLeafTasksAvgProgress($parent->id, $parent->path);
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
        // Todos cancelados o eliminados
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

        // Todos completados
        if ($active->every(fn ($c) => $c->task_status_id === TaskStatusEnum::COMPLETED->value)) {
            return TaskStatusEnum::COMPLETED->value;
        }

        // Alguno en progreso o en pausa
        if ($active->contains(fn ($c) => in_array($c->task_status_id, [
            TaskStatusEnum::IN_PROGRESS->value,
            TaskStatusEnum::ON_HOLD->value,
        ]))) {
            return TaskStatusEnum::IN_PROGRESS->value;
        }

        // Alguno completado pero no todos
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

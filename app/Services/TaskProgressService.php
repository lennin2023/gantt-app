<?php

namespace App\Services;

use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Models\Task;
use Illuminate\Support\Collection;

class TaskProgressService
{
    public function recalculateAncestors(Task $task): void
    {
        $parent = $task->parent_id ? Task::find($task->parent_id) : null;

        while ($parent) {
            $changed = $this->recalculate($parent);

            if (! $changed) {
                break;
            }

            $parent = $parent->parent_id ? Task::find($parent->parent_id) : null;
        }
    }

    public function recalculateById(int $taskId): void
    {
        $task = Task::find($taskId);

        if (! $task) {
            return;
        }

        $this->recalculate($task);

        if ($task->parent_id) {
            $this->recalculateAncestors($task);
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
            ->where('type', '!=', TaskTypeEnum::MILESTONE->value)
            ->get();

        if ($children->isEmpty()) {
            return false;
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

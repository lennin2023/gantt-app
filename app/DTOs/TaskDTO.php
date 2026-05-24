<?php

namespace App\DTOs;

use App\Models\Task;

class TaskDTO
{
    public function __construct(
        public readonly int $projectUserId,
        public readonly ?int $taskStatusId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly ?int $progress,
        public readonly ?int $order,
        public readonly int $createdBy,
        public readonly ?int $updatedBy = null,
        public readonly array $dependencyIds = [],
    ) {}

    public static function fromArray(array $data, int $createdBy): self
    {
        return new self(
            projectUserId: $data['project_user_id'],
            taskStatusId: $data['task_status_id'] ?? null,
            name: $data['name'],
            description: $data['description'] ?? null,
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            progress: $data['progress'] ?? null,
            order: $data['order'] ?? null,
            createdBy: $createdBy,
            updatedBy: $data['updated_by'] ?? null,
            dependencyIds: $data['dependency_ids'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'project_user_id' => $this->projectUserId,
            'task_status_id' => $this->taskStatusId,
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'progress' => $this->progress,
            'order' => $this->order,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
        ], fn ($value) => $value !== null);
    }

    public static function fromEntity(Task $task): self
    {
        return new self(
            projectUserId: $task->project_user_id,
            taskStatusId: $task->task_status_id,
            name: $task->name,
            description: $task->description,
            startDate: $task->start_date?->format('Y-m-d'),
            endDate: $task->end_date?->format('Y-m-d'),
            progress: $task->progress,
            order: $task->order,
            createdBy: $task->created_by,
            updatedBy: $task->updated_by,
            dependencyIds: $task->relationLoaded('dependencies')
                ? $task->dependencies->pluck('id')->toArray()
                : [],
        );
    }
}

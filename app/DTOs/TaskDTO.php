<?php

namespace App\DTOs;

class TaskDTO
{
    public function __construct(
        public readonly int $projectId,
        public readonly ?int $taskStatusId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?int $assignedTo,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly ?int $progress,
        public readonly ?int $order,
        public readonly int $createdBy,
        public readonly ?int $updatedBy = null,
        public readonly array $dependencyIds = [],
    ) {}

    public static function fromArray(array $data, int $createdBy, ?int $fallbackProjectId = null): self
    {
        return new self(
            projectId: $data['project_id'] ?? $fallbackProjectId,
            taskStatusId: $data['task_status_id'] ?? null,
            name: $data['name'],
            description: $data['description'] ?? null,
            assignedTo: $data['assigned_to'] ?? null,
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
        return [
            'project_id' => $this->projectId,
            'task_status_id' => $this->taskStatusId,
            'name' => $this->name,
            'description' => $this->description,
            'assigned_to' => $this->assignedTo,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'progress' => $this->progress,
            'order' => $this->order,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
        ];
    }
}

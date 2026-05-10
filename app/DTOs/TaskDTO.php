<?php

namespace App\DTOs;

class TaskDTO
{
    public function __construct(
        public readonly int $projectId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $assignee,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly int $progress,
        public readonly string $status,
        public readonly int $order,
        public readonly array $dependencyIds = [],
    ) {}

    public static function fromArray(array $data, ?int $fallbackProjectId = null): self
    {
        return new self(
            projectId: $data['project_id'] ?? $fallbackProjectId,
            name: $data['name'],
            description: $data['description'] ?? null,
            assignee: $data['assignee'] ?? null,
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            progress: $data['progress'] ?? 0,
            status: $data['status'] ?? 'pending',
            order: $data['order'] ?? 0,
            dependencyIds: $data['dependency_ids'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'name' => $this->name,
            'description' => $this->description,
            'assignee' => $this->assignee,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'progress' => $this->progress,
            'status' => $this->status,
            'order' => $this->order,
        ];
    }
}

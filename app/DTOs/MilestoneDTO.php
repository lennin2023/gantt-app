<?php

namespace App\DTOs;

class MilestoneDTO
{
    public function __construct(
        public readonly int $projectId,
        public readonly string $name,
        public readonly string $date,
        public readonly bool $reached = false,
        public readonly ?int $createdBy = null,
        public readonly ?int $updatedBy = null,
    ) {}

    public static function fromArray(array $data, int $projectId): self
    {
        return new self(
            projectId: $projectId,
            name: $data['name'],
            date: $data['date'],
            reached: $data['reached'] ?? false,
            createdBy: $data['created_by'] ?? null,
            updatedBy: $data['updated_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'name' => $this->name,
            'date' => $this->date,
            'reached' => $this->reached,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
        ];
    }
}

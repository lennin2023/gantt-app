<?php

namespace App\DTOs;

class MilestoneDTO
{
    public function __construct(
        public readonly int $projectId,
        public readonly string $name,
        public readonly string $date,
        public readonly bool $reached = false,
    ) {}

    public static function fromArray(array $data, int $projectId): self
    {
        return new self(
            projectId: $projectId,
            name: $data['name'],
            date: $data['date'],
            reached: $data['reached'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'name' => $this->name,
            'date' => $this->date,
            'reached' => $this->reached,
        ];
    }
}

<?php

namespace App\DTOs;

class ProjectDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $color,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            name: $data['name'],
            description: $data['description'] ?? null,
            color: $data['color'] ?? '#3b82f6',
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];
    }
}

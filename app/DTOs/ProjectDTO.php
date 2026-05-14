<?php

namespace App\DTOs;

use App\Enums\ProjectStatusEnum;

class ProjectDTO
{
    public function __construct(
        public readonly int $companyId,
        public readonly ?int $projectStatusId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $color,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly int $createdBy,
    ) {}

    public static function fromArray(array $data, int $createdBy): self
    {
        return new self(
            companyId: $data['company_id'],
            projectStatusId: $data['project_status_id'] ?? ProjectStatusEnum::ACTIVE->value,
            name: $data['name'],
            description: $data['description'] ?? null,
            color: $data['color'] ?? '#3b82f6',
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            createdBy: $createdBy,
        );
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'project_status_id' => $this->projectStatusId,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'created_by' => $this->createdBy,
        ];
    }
}

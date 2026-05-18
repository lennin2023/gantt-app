<?php

namespace App\DTOs;

use App\Models\Project;

class ProjectDTO
{
    public function __construct(
        public readonly ?int $companyId,
        public readonly ?int $projectStatusId,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?string $color,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly int $createdBy,
        public readonly ?int $updatedBy = null,
    ) {}

    public static function fromArray(array $data, int $createdBy): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            projectStatusId: isset($data['project_status_id']) ? (int) $data['project_status_id'] : null,
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            color: $data['color'] ?? null,
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            createdBy: $createdBy,
            updatedBy: $data['updated_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'project_status_id' => $this->projectStatusId,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
        ], fn ($value) => $value !== null);
    }

    public static function fromEntity(Project $project): self
    {
        return new self(
            companyId: $project->company_id,
            projectStatusId: $project->project_status_id,
            name: $project->name,
            description: $project->description,
            color: $project->color,
            startDate: $project->start_date?->format('Y-m-d'),
            endDate: $project->end_date?->format('Y-m-d'),
            createdBy: $project->created_by,
            updatedBy: $project->updated_by,
        );
    }
}

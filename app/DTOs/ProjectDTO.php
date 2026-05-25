<?php

namespace App\DTOs;

use App\Models\Project;
use Illuminate\Contracts\Support\Arrayable;

class ProjectDTO implements Arrayable
{
    public function __construct(
        public readonly int $createdBy,
        public readonly ?int $companyId = null,
        public readonly ?int $projectStatusId = null,
        public readonly ?string $name = null,
        public readonly ?string $color = null,
        public readonly ?int $updatedBy = null,
        public readonly ?string $description = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
    ) {}

    public static function fromArray(array $data, int $createdBy): self
    {
        return new self(
            createdBy: $createdBy,
            companyId: $data['company_id'] ?? null,
            projectStatusId: isset($data['project_status_id']) ? (int) $data['project_status_id'] : null,
            name: $data['name'] ?? null,
            color: $data['color'] ?? null,
            updatedBy: $data['updated_by'] ?? null,
            description: array_key_exists('description', $data) ? $data['description'] : null,
            startDate: array_key_exists('start_date', $data) ? $data['start_date'] : null,
            endDate: array_key_exists('end_date', $data) ? $data['end_date'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'created_by' => $this->createdBy,
        ];

        // Campos que no pueden ser null
        if ($this->companyId !== null) {
            $data['company_id'] = $this->companyId;
        }
        if ($this->projectStatusId !== null) {
            $data['project_status_id'] = $this->projectStatusId;
        }
        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->color !== null) {
            $data['color'] = $this->color;
        }
        if ($this->updatedBy !== null) {
            $data['updated_by'] = $this->updatedBy;
        }

        // Campos que sí pueden ser null explícitamente
        $data['description'] = $this->description;
        $data['start_date'] = $this->startDate;
        $data['end_date'] = $this->endDate;

        return $data;
    }

    public static function fromEntity(Project $project): self
    {
        return new self(
            createdBy: $project->created_by,
            companyId: $project->company_id,
            projectStatusId: $project->project_status_id,
            name: $project->name,
            color: $project->color,
            updatedBy: $project->updated_by,
            description: $project->description,
            startDate: $project->start_date?->format('Y-m-d'),
            endDate: $project->end_date?->format('Y-m-d'),
        );
    }
}

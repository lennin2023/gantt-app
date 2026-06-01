<?php

namespace App\DTOs;

use App\Models\Project;
use Illuminate\Contracts\Support\Arrayable;

class ProjectDTO implements Arrayable
{
    public const UNDEFINED = '__UNDEFINED__';

    public function __construct(
        public readonly ?int $companyId = null,
        public readonly ?int $projectStatusId = null,
        public readonly ?string $name = null,
        public readonly ?string $color = null,
        public readonly mixed $description = self::UNDEFINED,
        public readonly mixed $startDate = self::UNDEFINED,
        public readonly mixed $endDate = self::UNDEFINED,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            projectStatusId: isset($data['project_status_id']) ? (int) $data['project_status_id'] : null,
            name: $data['name'] ?? null,
            color: $data['color'] ?? null,
            description: array_key_exists('description', $data) ? $data['description'] : self::UNDEFINED,
            startDate: array_key_exists('start_date', $data) ? $data['start_date'] : self::UNDEFINED,
            endDate: array_key_exists('end_date', $data) ? $data['end_date'] : self::UNDEFINED,
        );
    }

    public function toArray(): array
    {
        $data = [];

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
        if ($this->description !== self::UNDEFINED) {
            $data['description'] = $this->description;
        }
        if ($this->startDate !== self::UNDEFINED) {
            $data['start_date'] = $this->startDate;
        }
        if ($this->endDate !== self::UNDEFINED) {
            $data['end_date'] = $this->endDate;
        }

        return $data;
    }

    public static function fromEntity(Project $project): self
    {
        return new self(
            companyId: $project->company_id,
            projectStatusId: $project->project_status_id,
            name: $project->name,
            color: $project->color,
            description: $project->description,
            startDate: $project->start_date?->format('Y-m-d'),
            endDate: $project->end_date?->format('Y-m-d'),
        );
    }
}

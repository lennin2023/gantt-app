<?php

namespace App\DTOs;

use App\Models\Milestone;
use Illuminate\Contracts\Support\Arrayable;

class MilestoneDTO implements Arrayable
{
    public const UNDEFINED = '__UNDEFINED__';

    public function __construct(
        public readonly int $projectId,
        public readonly ?string $name = null,
        public readonly ?string $date = null,
        public readonly ?int $createdBy = null,
        public readonly ?int $updatedBy = null,
        public readonly mixed $reached = self::UNDEFINED,
    ) {}

    public static function fromArray(array $data, int $projectId): self
    {
        return new self(
            projectId: $projectId,
            name: $data['name'] ?? null,
            date: $data['date'] ?? null,
            createdBy: $data['created_by'] ?? null,
            updatedBy: $data['updated_by'] ?? null,
            reached: array_key_exists('reached', $data) ? $data['reached'] : self::UNDEFINED,
        );
    }

    public function toArray(): array
    {
        $data = [
            'project_id' => $this->projectId,
        ];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->date !== null) {
            $data['date'] = $this->date;
        }
        if ($this->createdBy !== null) {
            $data['created_by'] = $this->createdBy;
        }
        if ($this->updatedBy !== null) {
            $data['updated_by'] = $this->updatedBy;
        }
        if ($this->reached !== self::UNDEFINED) {
            $data['reached'] = $this->reached;
        }

        return $data;
    }

    public static function fromEntity(Milestone $milestone): self
    {
        return new self(
            projectId: $milestone->project_id,
            name: $milestone->name,
            date: $milestone->date?->format('Y-m-d'),
            createdBy: $milestone->created_by,
            updatedBy: $milestone->updated_by,
            reached: $milestone->reached,
        );
    }
}

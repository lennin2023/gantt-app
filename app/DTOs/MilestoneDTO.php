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
        public readonly mixed $reached = self::UNDEFINED,
        public readonly mixed $isActive = self::UNDEFINED,
    ) {}

    public static function fromArray(array $data, int $projectId): self
    {
        return new self(
            projectId: $projectId,
            name: $data['name'] ?? null,
            date: $data['date'] ?? null,
            reached: array_key_exists('reached', $data) ? $data['reached'] : self::UNDEFINED,
            isActive: array_key_exists('is_active', $data) ? $data['is_active'] : self::UNDEFINED,
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
        if ($this->reached !== self::UNDEFINED) {
            $data['reached'] = $this->reached;
        }
        if ($this->isActive !== self::UNDEFINED) {
            $data['is_active'] = $this->isActive;
        }

        return $data;
    }

    public static function fromEntity(Milestone $milestone): self
    {
        return new self(
            projectId: $milestone->project_id,
            name: $milestone->name,
            date: $milestone->date?->format('Y-m-d'),
            reached: $milestone->reached,
        );
    }
}

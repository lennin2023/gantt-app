<?php

namespace App\DTOs;

use App\Models\Task;
use Illuminate\Contracts\Support\Arrayable;

class TaskDTO implements Arrayable
{
    public const UNDEFINED = '__UNDEFINED__';

    public const UNDEFINED_ARRAY = '__UNDEFINED_ARRAY__';

    public function __construct(
        public readonly int $projectId,
        public readonly ?string $type = null,
        public readonly ?int $parentId = null,
        public readonly ?int $taskStatusId = null,
        public readonly ?string $title = null,
        public readonly mixed $description = self::UNDEFINED,
        public readonly mixed $startDate = self::UNDEFINED,
        public readonly mixed $endDate = self::UNDEFINED,
        public readonly ?int $progress = null,
        public readonly mixed $dependencyIds = self::UNDEFINED_ARRAY,
        public readonly ?string $dependencyType = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            projectId: $data['project_id'],
            type: $data['type'] ?? null,
            parentId: $data['parent_id'] ?? null,
            taskStatusId: $data['task_status_id'] ?? null,
            title: $data['title'] ?? null,
            description: array_key_exists('description', $data) ? $data['description'] : self::UNDEFINED,
            startDate: array_key_exists('start_date', $data) ? $data['start_date'] : self::UNDEFINED,
            endDate: array_key_exists('end_date', $data) ? $data['end_date'] : self::UNDEFINED,
            progress: $data['progress'] ?? null,
            dependencyIds: array_key_exists('dependency_ids', $data) ? $data['dependency_ids'] : self::UNDEFINED_ARRAY,
            dependencyType: $data['dependency_type'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'project_id' => $this->projectId,
        ];

        if ($this->type !== null) {
            $data['type'] = $this->type;
        }
        if ($this->parentId !== null) {
            $data['parent_id'] = $this->parentId;
        }
        if ($this->taskStatusId !== null) {
            $data['task_status_id'] = $this->taskStatusId;
        }
        if ($this->title !== null) {
            $data['title'] = $this->title;
        }
        if ($this->progress !== null) {
            $data['progress'] = $this->progress;
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

    public static function fromEntity(Task $task): self
    {
        return new self(
            projectId: $task->project_id,
            type: $task->type->value,
            parentId: $task->parent_id,
            taskStatusId: $task->task_status_id,
            title: $task->title,
            description: $task->description,
            startDate: $task->start_date?->format('Y-m-d'),
            endDate: $task->end_date?->format('Y-m-d'),
            progress: $task->progress,
            dependencyIds: $task->relationLoaded('dependencies')
                ? $task->dependencies->pluck('id')->toArray()
                : [],
        );
    }
}

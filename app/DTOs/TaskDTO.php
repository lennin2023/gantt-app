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
        public readonly int $createdBy,
        public readonly ?int $parentId = null,
        public readonly ?int $taskStatusId = null,
        public readonly ?string $title = null,
        public readonly ?int $updatedBy = null,
        public readonly mixed $description = self::UNDEFINED,
        public readonly mixed $startDate = self::UNDEFINED,
        public readonly mixed $endDate = self::UNDEFINED,
        public readonly ?int $progress = null,
        public readonly ?int $order = null,
        public readonly mixed $dependencyIds = self::UNDEFINED_ARRAY,
    ) {}

    public static function fromArray(array $data, int $createdBy): self
    {
        return new self(
            projectId: $data['project_id'],
            createdBy: $createdBy,
            parentId: $data['parent_id'] ?? null,
            taskStatusId: $data['task_status_id'] ?? null,
            title: $data['title'] ?? null,
            updatedBy: $data['updated_by'] ?? null,
            description: array_key_exists('description', $data) ? $data['description'] : self::UNDEFINED,
            startDate: array_key_exists('start_date', $data) ? $data['start_date'] : self::UNDEFINED,
            endDate: array_key_exists('end_date', $data) ? $data['end_date'] : self::UNDEFINED,
            progress: $data['progress'] ?? null,
            order: $data['order'] ?? null,
            dependencyIds: array_key_exists('dependency_ids', $data) ? $data['dependency_ids'] : self::UNDEFINED_ARRAY,
        );
    }

    public function toArray(): array
    {
        $data = [
            'project_id' => $this->projectId,
            'created_by' => $this->createdBy,
        ];

        if ($this->parentId !== null) {
            $data['parent_id'] = $this->parentId;
        }
        if ($this->taskStatusId !== null) {
            $data['task_status_id'] = $this->taskStatusId;
        }
        if ($this->title !== null) {
            $data['title'] = $this->title;
        }
        if ($this->updatedBy !== null) {
            $data['updated_by'] = $this->updatedBy;
        }
        if ($this->progress !== null) {
            $data['progress'] = $this->progress;
        }
        if ($this->order !== null) {
            $data['order'] = $this->order;
        }

        // Campos que sí pueden ser null explícitamente
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
            createdBy: $task->created_by,
            parentId: $task->parent_id,
            taskStatusId: $task->task_status_id,
            title: $task->title,
            updatedBy: $task->updated_by,
            description: $task->description,
            startDate: $task->start_date?->format('Y-m-d'),
            endDate: $task->end_date?->format('Y-m-d'),
            progress: $task->progress,
            order: $task->order,
            dependencyIds: $task->relationLoaded('dependencies')
                ? $task->dependencies->pluck('id')->toArray()
                : [],
        );
    }
}

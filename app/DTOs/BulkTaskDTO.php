<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

class BulkTaskDTO implements Arrayable
{
    public const UNDEFINED = '__UNDEFINED__';

    public function __construct(
        public readonly array $taskIds,
        public readonly mixed $taskStatusId = self::UNDEFINED,
        public readonly mixed $title = self::UNDEFINED,
        public readonly mixed $description = self::UNDEFINED,
        public readonly mixed $startDate = self::UNDEFINED,
        public readonly mixed $endDate = self::UNDEFINED,
        public readonly mixed $progress = self::UNDEFINED,
        public readonly mixed $order = self::UNDEFINED,
    ) {}

    public static function fromArray(array $data): self
    {
        $fields = $data['data'] ?? [];

        return new self(
            taskIds: $data['task_ids'],
            taskStatusId: array_key_exists('task_status_id', $fields) ? $fields['task_status_id'] : self::UNDEFINED,
            title: array_key_exists('title', $fields) ? $fields['title'] : self::UNDEFINED,
            description: array_key_exists('description', $fields) ? $fields['description'] : self::UNDEFINED,
            startDate: array_key_exists('start_date', $fields) ? $fields['start_date'] : self::UNDEFINED,
            endDate: array_key_exists('end_date', $fields) ? $fields['end_date'] : self::UNDEFINED,
            progress: array_key_exists('progress', $fields) ? $fields['progress'] : self::UNDEFINED,
            order: array_key_exists('order', $fields) ? $fields['order'] : self::UNDEFINED,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->taskStatusId !== self::UNDEFINED) {
            $data['task_status_id'] = $this->taskStatusId;
        }
        if ($this->title !== self::UNDEFINED) {
            $data['title'] = $this->title;
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
        if ($this->progress !== self::UNDEFINED) {
            $data['progress'] = $this->progress;
        }
        if ($this->order !== self::UNDEFINED) {
            $data['order'] = $this->order;
        }

        return $data;
    }
}

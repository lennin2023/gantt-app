<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

class TaskAssignmentDTO implements Arrayable
{
    public const UNDEFINED = '__UNDEFINED__';

    public function __construct(
        public readonly int $taskId,
        public readonly ?int $projectUserId = null,
        public readonly mixed $taskRoleId = self::UNDEFINED,
    ) {}

    public static function fromArray(array $data, int $taskId): self
    {
        return new self(
            taskId: $taskId,
            projectUserId: $data['project_user_id'] ?? null,
            taskRoleId: array_key_exists('task_role_id', $data) ? $data['task_role_id'] : self::UNDEFINED,
        );
    }

    public function toArray(): array
    {
        $data = [
            'task_id' => $this->taskId,
        ];

        if ($this->projectUserId !== null) {
            $data['project_user_id'] = $this->projectUserId;
        }
        if ($this->taskRoleId !== self::UNDEFINED) {
            $data['task_role_id'] = $this->taskRoleId;
        }

        return $data;
    }
}

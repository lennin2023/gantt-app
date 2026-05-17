<?php

namespace App\DTOs;

class ProjectUserDTO
{
    public function __construct(
        public readonly int $projectId,
        public readonly int $userId,
        public readonly int $projectRoleId,
        public readonly int $createdBy,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            projectId: $data['project_id'],
            userId: $data['user_id'],
            projectRoleId: $data['project_role_id'],
            createdBy: $data['created_by'],
        );
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'user_id' => $this->userId,
            'project_role_id' => $this->projectRoleId,
            'created_by' => $this->createdBy,
        ];
    }
}

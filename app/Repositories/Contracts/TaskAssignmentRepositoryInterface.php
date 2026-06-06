<?php

namespace App\Repositories\Contracts;

use App\Models\TaskAssignment;
use Illuminate\Support\Collection;

interface TaskAssignmentRepositoryInterface
{
    public function getAllByTask(int $taskId): Collection;

    public function findById(int $id): ?TaskAssignment;

    public function findByTaskAndProjectUser(int $taskId, int $projectUserId): ?TaskAssignment;

    public function create(array $data): TaskAssignment;

    public function update(TaskAssignment $assignment, array $data): TaskAssignment;

    public function delete(TaskAssignment $assignment): void;

    public function exists(int $taskId, int $projectUserId): bool;
}

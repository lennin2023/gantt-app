<?php

namespace App\Repositories\Eloquent;

use App\Models\TaskAssignment;
use App\Repositories\Contracts\TaskAssignmentRepositoryInterface;
use Illuminate\Support\Collection;

class TaskAssignmentRepository implements TaskAssignmentRepositoryInterface
{
    public function getAllByTask(int $taskId): Collection
    {
        return TaskAssignment::with(['projectUser.user', 'taskRole'])
            ->where('task_id', $taskId)
            ->get();
    }

    public function findById(int $id): ?TaskAssignment
    {
        return TaskAssignment::with(['projectUser.user', 'taskRole'])->find($id);
    }

    public function findByTaskAndProjectUser(int $taskId, int $projectUserId): ?TaskAssignment
    {
        return TaskAssignment::where('task_id', $taskId)
            ->where('project_user_id', $projectUserId)
            ->first();
    }

    public function create(array $data): TaskAssignment
    {
        return TaskAssignment::create($data);
    }

    public function update(TaskAssignment $assignment, array $data): TaskAssignment
    {
        $assignment->update($data);

        return $assignment->fresh(['projectUser.user', 'taskRole']);
    }

    public function delete(TaskAssignment $assignment): void
    {
        $assignment->delete();
    }

    public function exists(int $taskId, int $projectUserId): bool
    {
        return TaskAssignment::where('task_id', $taskId)
            ->where('project_user_id', $projectUserId)
            ->exists();
    }
}

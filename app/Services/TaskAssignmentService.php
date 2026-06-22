<?php

namespace App\Services;

use App\DTOs\TaskAssignmentDTO;
use App\Exceptions\TaskAssignmentAlreadyExistsException;
use App\Models\TaskAssignment;
use App\Repositories\Contracts\TaskAssignmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TaskAssignmentService
{
    public function __construct(
        private readonly TaskAssignmentRepositoryInterface $assignmentRepository,
    ) {}

    public function getTaskAssignments(int $taskId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->assignmentRepository->getAllByTask($taskId, $perPage);
    }

    public function assign(TaskAssignmentDTO $dto): TaskAssignment
    {
        if ($this->assignmentRepository->exists($dto->taskId, $dto->projectUserId)) {
            throw new TaskAssignmentAlreadyExistsException;
        }

        return DB::transaction(function () use ($dto) {
            return $this->assignmentRepository->create($dto->toArray());
        });
    }

    public function updateRole(TaskAssignment $assignment, TaskAssignmentDTO $dto): TaskAssignment
    {
        return DB::transaction(function () use ($assignment, $dto) {
            return $this->assignmentRepository->update($assignment, $dto->toArray());
        });
    }

    public function unassign(TaskAssignment $assignment): void
    {
        DB::transaction(function () use ($assignment) {
            $this->assignmentRepository->delete($assignment);
        });
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\DTOs\TaskAssignmentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TaskAssignmentRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\TaskAssignmentResource;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Services\TaskAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskAssignmentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TaskAssignmentService $assignmentService,
    ) {}

    public function index(Task $task): AnonymousResourceCollection
    {
        $task->loadMissing('project');
        $this->authorize('viewAny', [TaskAssignment::class, $task->project]);

        $assignments = $this->assignmentService->getTaskAssignments($task->id);

        return TaskAssignmentResource::collection($assignments);
    }

    public function store(TaskAssignmentRequest $request, Task $task): JsonResponse
    {
        $task->loadMissing('project');
        $this->authorize('create', [TaskAssignment::class, $task->project]);

        $dto = TaskAssignmentDTO::fromArray($request->validated(), $task->id);
        $assignment = $this->assignmentService->assign($dto);

        return $this->created(new TaskAssignmentResource($assignment));
    }

    public function update(TaskAssignmentRequest $request, Task $task, TaskAssignment $assignment): JsonResponse
    {
        $task->loadMissing('project');
        $this->authorize('update', [TaskAssignment::class, $task->project]);

        $dto = TaskAssignmentDTO::fromArray($request->validated(), $task->id);
        $assignment = $this->assignmentService->updateRole($assignment, $dto);

        return $this->success(new TaskAssignmentResource($assignment));
    }

    public function destroy(Task $task, TaskAssignment $assignment): JsonResponse
    {
        $task->loadMissing('project');
        $this->authorize('delete', [TaskAssignment::class, $task->project]);

        $this->assignmentService->unassign($assignment);

        return $this->deleted('Assignment removed successfully');
    }
}

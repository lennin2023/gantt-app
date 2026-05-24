<?php

namespace App\Http\Controllers\Api;

use App\DTOs\TaskDTO;
use App\Exceptions\BulkOperationException;
use App\Exceptions\CycleDetectionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BulkDeleteTaskRequest;
use App\Http\Requests\Api\TaskRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        abort_unless(Gate::allows('view', $project), 403);

        $perPage = min((int) $request->query('per_page', 10), 100);

        $tasks = $this->taskService->getProjectTasks($project->id, $perPage);

        return TaskResource::collection($tasks);
    }

    public function store(TaskRequest $request, Project $project): JsonResponse
    {
        abort_unless(Gate::allows('create', $project), 403);

        $dto = TaskDTO::fromArray(
            array_merge($request->validated(), ['created_by' => Auth::id()]),
            Auth::id(),
        );

        $task = $this->taskService->createTask($dto);

        return $this->created(new TaskResource($task));
    }

    public function show(Task $task): JsonResponse
    {
        $task->load('projectUser.project');

        abort_unless($task && Gate::allows('view', $task->projectUser?->project), 403);

        return $this->success(new TaskResource($task));
    }

    public function update(TaskRequest $request, Task $task): JsonResponse
    {
        $task->load('projectUser.project');

        abort_unless(Gate::allows('update', $task->projectUser?->project), 403);

        $dto = TaskDTO::fromArray(
            array_merge($request->validated(), ['updated_by' => Auth::id()]),
            $task->created_by,
        );

        if (! empty($dto->dependencyIds)) {
            try {
                $this->taskService->validateNoCycleWouldBeCreated($task, $dto->dependencyIds);
            } catch (CycleDetectionException $e) {
                return $this->validationError($e->getMessage());
            }
        }

        $task = $this->taskService->updateTask($task, $dto);

        return $this->success(new TaskResource($task));
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->load('projectUser.project');

        abort_unless(Gate::allows('delete', $task->projectUser?->project), 403);

        $this->taskService->deleteTask($task);

        return $this->deleted('Task deleted successfully');
    }

    public function bulkUpdate(TaskRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $taskIds = $validated['task_ids'] ?? [];
        $data = $validated['data'] ?? [];

        try {
            $tasks = $this->taskService->validateAndGetTasksForBulkUpdate($taskIds, $data);
        } catch (BulkOperationException $e) {
            return $this->validationError($e->getMessage());
        }

        $project = $tasks->first()->projectUser?->project;
        abort_unless($project && Gate::allows('update', $project), 403);

        $updated = $this->taskService->bulkUpdate($tasks, $data);

        return $this->success([
            'tasks' => TaskResource::collection($updated),
        ], 'Tasks updated successfully');
    }

    public function bulkDelete(BulkDeleteTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $taskIds = $validated['task_ids'];

        try {
            $tasks = $this->taskService->validateAndGetTasksForBulkDelete($taskIds);
        } catch (BulkOperationException $e) {
            return $this->validationError($e->getMessage());
        }

        $project = $tasks->first()->projectUser?->project;
        abort_unless($project && Gate::allows('delete', $project), 403);

        $this->taskService->bulkDelete($tasks);

        return $this->deleted(count($taskIds).' tasks deleted successfully');
    }

    public function restore(Task $task): JsonResponse
    {
        $task->load('projectUser.project');

        abort_unless(Gate::allows('restore', $task->projectUser?->project), 403);

        $this->taskService->restoreTask($task);

        return $this->success(null, 'Task restored successfully');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\DTOs\TaskDTO;
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

class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $perPage = min((int) $request->query('per_page', 10), 100);

        $tasks = $this->taskService->getProjectTasks($project->id, $perPage);

        return TaskResource::collection($tasks);
    }

    public function store(TaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $dto = TaskDTO::fromArray(
            array_merge($request->validated(), [
                'project_id' => $project->id,
                'created_by' => Auth::id(),
            ]),
            Auth::id(),
        );

        $task = $this->taskService->createTask($dto);

        return $this->created(new TaskResource($task));
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', [Task::class, $task->project]);

        $task = $this->taskService->findById($task->id);

        return $this->success(new TaskResource($task));
    }

    public function update(TaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', [Task::class, $task->project]);

        $dto = TaskDTO::fromArray(
            array_merge($request->validated(), [
                'project_id' => $task->project_id,
                'updated_by' => Auth::id(),
            ]),
            $task->created_by,
        );

        $task = $this->taskService->updateTask($task, $dto);

        return $this->success(new TaskResource($task));
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', [Task::class, $task->project]);

        $this->taskService->cancelTask($task, Auth::id());

        return $this->deleted('Task cancelled successfully');
    }

    public function restore(Task $task): JsonResponse
    {
        $this->authorize('restore', [Task::class, $task->project]);

        $this->taskService->restoreTask($task, Auth::id());

        return $this->success(null, 'Task restored successfully');
    }

    public function bulkUpdate(TaskRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $taskIds = $validated['task_ids'] ?? [];
        $data = $validated['data'] ?? [];

        $tasks = $this->taskService->validateAndGetTasksForBulkUpdate($taskIds);

        $project = $tasks->first()->project;
        $this->authorize('update', [Task::class, $project]);

        $updated = $this->taskService->bulkUpdate($tasks, $data);

        return $this->success([
            'tasks' => TaskResource::collection($updated),
        ], 'Tasks updated successfully');
    }

    public function bulkDelete(BulkDeleteTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $taskIds = $validated['task_ids'];

        $tasks = $this->taskService->validateAndGetTasksForBulkDelete($taskIds);

        $project = $tasks->first()->project;
        $this->authorize('delete', [Task::class, $project]);

        $this->taskService->bulkCancel($tasks, Auth::id());

        return $this->deleted(count($taskIds).' tasks cancelled successfully');
    }
}

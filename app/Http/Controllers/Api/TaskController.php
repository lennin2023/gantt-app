<?php

namespace App\Http\Controllers\Api;

use App\DTOs\TaskDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TaskRequest;
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
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(Request $request, int $projectId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('view', $project), 403);

        $perPage = min((int) $request->query('per_page', 10), 100);

        $tasks = $this->taskService->getProjectTasks($projectId, $perPage);

        return TaskResource::collection($tasks);
    }

    public function store(TaskRequest $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('create', $project), 403);

        $dto = TaskDTO::fromArray(
            array_merge($request->validated(), ['created_by' => Auth::id()]),
            createdBy: Auth::id(),
            fallbackProjectId: $projectId,
        );

        $task = $this->taskService->createTask($dto);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): TaskResource
    {
        $task = $this->taskService->findById($id);

        abort_unless($task && Gate::allows('view', $task->project), 403);

        return new TaskResource($task);
    }

    public function update(TaskRequest $request, int $id): TaskResource
    {
        $task = Task::with('project')->findOrFail($id);

        abort_unless(Gate::allows('update', $task->project), 403);

        $dto = TaskDTO::fromArray(
            array_merge($request->validated(), ['updated_by' => Auth::id()]),
            createdBy: $task->created_by,
        );

        if (! empty($dto->dependencyIds)) {
            foreach ($dto->dependencyIds as $depId) {
                if ($this->taskService->wouldCreateCycle($task, $depId)) {
                    abort(422, 'Adding this dependency would create a cycle');
                }
            }
        }

        $task = $this->taskService->updateTask($task, $dto);

        return new TaskResource($task);
    }

    public function destroy(int $id): JsonResponse
    {
        $task = Task::with('project')->findOrFail($id);

        abort_unless(Gate::allows('delete', $task->project), 403);

        $this->taskService->deleteTask($task);

        return response()->json(['message' => 'Task deleted successfully']);
    }

    public function bulkUpdate(TaskRequest $request): JsonResponse
    {
        $taskIds = $request->validated()['task_ids'] ?? [];
        $data = $request->validated()['data'] ?? [];

        $tasks = Task::with('project')->whereIn('id', $taskIds)->get();

        foreach ($tasks as $task) {
            abort_unless(Gate::allows('update', $task->project), 403);
        }

        $updated = $this->taskService->bulkUpdate($tasks, $data);

        return response()->json([
            'message' => 'Tasks updated successfully',
            'tasks' => TaskResource::collection($updated),
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'task_ids' => 'required|array|min:1',
            'task_ids.*' => 'integer|exists:tasks,id',
        ]);

        $taskIds = $request->validated()['task_ids'];
        $tasks = Task::with('project')->whereIn('id', $taskIds)->get();

        foreach ($tasks as $task) {
            abort_unless(Gate::allows('delete', $task->project), 403);
        }

        $this->taskService->bulkDelete($tasks);

        return response()->json(['message' => count($taskIds).' tasks deleted successfully']);
    }

    public function restore(int $id): JsonResponse
    {
        $task = Task::withTrashed()->findOrFail($id);

        abort_unless(Gate::allows('restore', $task->project), 403);

        $this->taskService->restoreTask($task);

        return response()->json(['message' => 'Task restored successfully']);
    }
}

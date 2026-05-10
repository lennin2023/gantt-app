<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\TaskDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(int $projectId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('view', $project), 403);

        $tasks = $this->taskService->getProjectTasks($projectId);

        return TaskResource::collection($tasks);
    }

    public function store(TaskRequest $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('create', $project), 403);

        $data = $request->validated();
        $data['project_id'] = $projectId;

        $dto = TaskDTO::fromArray($data);

        if (! empty($dto->dependencyIds)) {
            $tempTask = new Task(['project_id' => $projectId]);
            foreach ($dto->dependencyIds as $depId) {
                if ($this->taskService->wouldCreateCycle($tempTask, $depId)) {
                    return response()->json([
                        'message' => 'Adding this dependency would create a cycle',
                    ], 422);
                }
            }
        }

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

        $dto = TaskDTO::fromArray($request->validated());

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

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }

    public function bulkUpdate(\App\Http\Requests\Api\V1\TaskRequest $request): JsonResponse
    {
        $taskIds = $request->validated()['task_ids'] ?? [];
        $data = $request->validated()['data'] ?? [];

        $tasks = Task::whereIn('id', $taskIds)->get();

        foreach ($tasks as $task) {
            abort_unless(Gate::allows('update', $task->project), 403);
        }

        $updated = $this->taskService->bulkUpdate($taskIds, $data);

        return response()->json([
            'message' => 'Tasks updated successfully',
            'tasks' => TaskResource::collection($updated),
        ]);
    }

    public function bulkDelete(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'task_ids' => 'required|array|min:1',
            'task_ids.*' => 'integer|exists:tasks,id',
        ]);

        $taskIds = $request->validated()['task_ids'];
        $tasks = Task::whereIn('id', $taskIds)->get();

        foreach ($tasks as $task) {
            abort_unless(Gate::allows('delete', $task->project), 403);
        }

        $deleted = $this->taskService->bulkDelete($taskIds);

        return response()->json([
            'message' => "$deleted tasks deleted successfully",
        ]);
    }
}

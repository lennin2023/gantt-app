<?php

namespace App\Http\Controllers\Api;

use App\DTOs\BulkTaskDTO;
use App\DTOs\TaskDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TaskRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OAT;

class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    #[OAT\Get(
        path: '/projects/{project}/tasks',
        tags: ['Tareas'],
        summary: 'Listar tareas',
        description: 'Lista paginada de tareas de un proyecto',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'project', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
            new OAT\Parameter(name: 'per_page', in: 'query', schema: new OAT\Schema(type: 'integer', default: 10)),
            new OAT\Parameter(name: 'status_id', in: 'query', schema: new OAT\Schema(type: 'integer')),
            new OAT\Parameter(name: 'type', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'search', in: 'query', schema: new OAT\Schema(type: 'string')),
            new OAT\Parameter(name: 'start_date_from', in: 'query', schema: new OAT\Schema(type: 'string', format: 'date')),
            new OAT\Parameter(name: 'start_date_to', in: 'query', schema: new OAT\Schema(type: 'string', format: 'date')),
            new OAT\Parameter(name: 'assignee_id', in: 'query', schema: new OAT\Schema(type: 'integer')),
        ],
        responses: [
            new OAT\Response(response: 200, description: 'Lista de tareas'),
            new OAT\Response(response: 404, description: 'Proyecto no encontrado'),
        ]
    )]
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $perPage = min((int) $request->query('per_page', 10), 100);

        $filters = array_filter([
            'status_id' => $request->query('status_id'),
            'type' => $request->query('type'),
            'search' => $request->query('search'),
            'start_date_from' => $request->query('start_date_from'),
            'start_date_to' => $request->query('start_date_to'),
            'assignee_id' => $request->query('assignee_id'),
        ]);

        $tasks = $this->taskService->getProjectTasks($project->id, $perPage, $filters);

        return TaskResource::collection($tasks);
    }

    #[OAT\Post(
        path: '/projects/{project}/tasks',
        tags: ['Tareas'],
        summary: 'Crear tarea',
        description: 'Crea una tarea dentro de un proyecto',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'project', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
        ],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['type', 'title'],
                properties: [
                    new OAT\Property(property: 'type', type: 'string'),
                    new OAT\Property(property: 'parent_id', type: 'integer', nullable: true),
                    new OAT\Property(property: 'task_status_id', type: 'integer'),
                    new OAT\Property(property: 'title', type: 'string'),
                    new OAT\Property(property: 'description', type: 'string', nullable: true),
                    new OAT\Property(property: 'start_date', type: 'string', format: 'date'),
                    new OAT\Property(property: 'end_date', type: 'string', format: 'date'),
                    new OAT\Property(property: 'progress', type: 'integer'),
                    new OAT\Property(property: 'dependency_ids', type: 'array', items: new OAT\Items(type: 'integer')),
                    new OAT\Property(property: 'dependency_type', type: 'string'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Tarea creada'),
            new OAT\Response(response: 422, description: 'Error de validación o ciclo detectado'),
        ]
    )]
    public function store(TaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $dto = TaskDTO::fromArray(array_merge($request->validated(), ['project_id' => $project->id]));
        $task = $this->taskService->createTask($dto);

        return $this->created(new TaskResource($task));
    }

    #[OAT\Get(
        path: '/tasks/{task}',
        tags: ['Tareas'],
        summary: 'Detalle de tarea',
        description: 'Devuelve el detalle de una tarea con relaciones',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'task', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
        ],
        responses: [
            new OAT\Response(response: 200, description: 'Detalle de la tarea'),
            new OAT\Response(response: 404, description: 'No encontrada'),
        ]
    )]
    public function show(Task $task): JsonResponse
    {
        $task = $this->taskService->findById($task->id);
        $this->authorize('view', [Task::class, $task->project]);

        return $this->success(new TaskResource($task));
    }

    #[OAT\Patch(
        path: '/tasks/{task}',
        tags: ['Tareas'],
        summary: 'Actualizar tarea',
        description: 'Actualización parcial de una tarea',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'task', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
        ],
        requestBody: new OAT\RequestBody(
            content: new OAT\JsonContent(
                properties: [
                    new OAT\Property(property: 'title', type: 'string'),
                    new OAT\Property(property: 'description', type: 'string', nullable: true),
                    new OAT\Property(property: 'task_status_id', type: 'integer'),
                    new OAT\Property(property: 'progress', type: 'integer'),
                    new OAT\Property(property: 'start_date', type: 'string', format: 'date'),
                    new OAT\Property(property: 'end_date', type: 'string', format: 'date'),
                    new OAT\Property(property: 'parent_id', type: 'integer', nullable: true),
                    new OAT\Property(property: 'dependency_ids', type: 'array', items: new OAT\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Tarea actualizada'),
            new OAT\Response(response: 422, description: 'Transición de estado inválida'),
        ]
    )]
    public function update(TaskRequest $request, Task $task): JsonResponse
    {
        $task->loadMissing('project');
        $this->authorize('update', [Task::class, $task->project]);

        $dto = TaskDTO::fromArray(array_merge($request->validated(), ['project_id' => $task->project_id]));
        $task = $this->taskService->updateTask($task, $dto);

        return $this->success(new TaskResource($task));
    }

    #[OAT\Delete(
        path: '/tasks/{task}',
        tags: ['Tareas'],
        summary: 'Eliminar tarea',
        description: 'Eliminación lógica con cascada a descendientes',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'task', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
        ],
        responses: [
            new OAT\Response(response: 200, description: 'Tarea eliminada'),
            new OAT\Response(response: 422, description: 'Estado no permite eliminación'),
        ]
    )]
    public function destroy(Task $task): JsonResponse
    {
        $task->loadMissing('project');
        $this->authorize('delete', [Task::class, $task->project]);

        $this->taskService->deleteTask($task);

        return $this->deleted('Task deleted successfully');
    }

    #[OAT\Post(
        path: '/tasks/{task}/restore',
        tags: ['Tareas'],
        summary: 'Restaurar tarea',
        description: 'Restaura una tarea eliminada con cascada',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'task', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
        ],
        responses: [
            new OAT\Response(response: 200, description: 'Tarea restaurada'),
            new OAT\Response(response: 422, description: 'La tarea no está eliminada'),
        ]
    )]
    public function restore(Task $task): JsonResponse
    {
        $task->loadMissing('project');
        $this->authorize('restore', [Task::class, $task->project]);

        $this->taskService->restoreTask($task);

        return $this->success(null, 'Task restored successfully');
    }

    #[OAT\Patch(
        path: '/tasks/bulk-update',
        tags: ['Tareas'],
        summary: 'Actualización masiva',
        description: 'Actualiza múltiples tareas con los mismos datos',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['task_ids', 'data'],
                properties: [
                    new OAT\Property(property: 'task_ids', type: 'array', items: new OAT\Items(type: 'integer')),
                    new OAT\Property(property: 'data', type: 'object',
                        properties: [
                            new OAT\Property(property: 'task_status_id', type: 'integer'),
                            new OAT\Property(property: 'title', type: 'string'),
                            new OAT\Property(property: 'progress', type: 'integer'),
                            new OAT\Property(property: 'start_date', type: 'string', format: 'date'),
                            new OAT\Property(property: 'end_date', type: 'string', format: 'date'),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Tareas actualizadas'),
            new OAT\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function bulkUpdate(TaskRequest $request): JsonResponse
    {
        $dto = BulkTaskDTO::fromArray($request->validated());
        $tasks = $this->taskService->validateAndGetTasksForBulkUpdate($dto->taskIds);
        $project = $tasks->first()->project()->firstOrFail();

        $this->authorize('update', [Task::class, $project]);

        $updated = $this->taskService->bulkUpdate($tasks, $dto);

        return $this->success([
            'tasks' => TaskResource::collection($updated),
        ], 'Tasks updated successfully');
    }
}

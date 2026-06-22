<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ProjectDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProjectRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OAT;

class ProjectController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    #[OAT\Get(
        path: '/projects',
        tags: ['Proyectos'],
        summary: 'Listar proyectos',
        description: 'Lista paginada de proyectos del usuario',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'per_page', in: 'query', schema: new OAT\Schema(type: 'integer', default: 10)),
            new OAT\Parameter(name: 'status_id', in: 'query', schema: new OAT\Schema(type: 'integer')),
        ],
        responses: [
            new OAT\Response(response: 200, description: 'Lista de proyectos'),
            new OAT\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $perPage = min((int) $request->query('per_page', 10), 100);
        $statusId = $request->query('status_id') ? (int) $request->query('status_id') : null;

        $projects = $this->projectService->getUserProjects(
            userId: Auth::id(),
            perPage: $perPage,
            statusId: $statusId,
        );

        return ProjectResource::collection($projects);
    }

    #[OAT\Post(
        path: '/projects',
        tags: ['Proyectos'],
        summary: 'Crear proyecto',
        description: 'Crea un nuevo proyecto (solo Admin o Super Admin)',
        security: [['sanctum' => []]],
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['company_id', 'name', 'color'],
                properties: [
                    new OAT\Property(property: 'company_id', type: 'integer'),
                    new OAT\Property(property: 'project_status_id', type: 'integer'),
                    new OAT\Property(property: 'name', type: 'string'),
                    new OAT\Property(property: 'description', type: 'string', nullable: true),
                    new OAT\Property(property: 'color', type: 'string'),
                    new OAT\Property(property: 'start_date', type: 'string', format: 'date'),
                    new OAT\Property(property: 'end_date', type: 'string', format: 'date'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 201, description: 'Proyecto creado'),
            new OAT\Response(response: 403, description: 'No autorizado'),
            new OAT\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(ProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $dto = ProjectDTO::fromArray($request->validated());
        $project = $this->projectService->createProject($dto);

        return $this->created(new ProjectResource($project));
    }

    #[OAT\Get(
        path: '/projects/{project}',
        tags: ['Proyectos'],
        summary: 'Detalle de proyecto',
        description: 'Devuelve el detalle de un proyecto',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'project', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
            new OAT\Parameter(name: 'include_stats', in: 'query', schema: new OAT\Schema(type: 'boolean')),
        ],
        responses: [
            new OAT\Response(response: 200, description: 'Detalle del proyecto'),
            new OAT\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project = $this->projectService->getProjectDetail($project);
        $resource = new ProjectResource($project);

        if (request()->query('include_stats')) {
            $resource->additional([
                'stats' => $this->projectService->getProjectStats($project),
            ]);
        }

        return $this->success($resource);
    }

    #[OAT\Patch(
        path: '/projects/{project}',
        tags: ['Proyectos'],
        summary: 'Actualizar proyecto',
        description: 'Actualización parcial de un proyecto',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'project', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
        ],
        requestBody: new OAT\RequestBody(
            content: new OAT\JsonContent(
                properties: [
                    new OAT\Property(property: 'name', type: 'string'),
                    new OAT\Property(property: 'project_status_id', type: 'integer'),
                    new OAT\Property(property: 'color', type: 'string'),
                    new OAT\Property(property: 'start_date', type: 'string', format: 'date'),
                    new OAT\Property(property: 'end_date', type: 'string', format: 'date'),
                ]
            )
        ),
        responses: [
            new OAT\Response(response: 200, description: 'Proyecto actualizado'),
            new OAT\Response(response: 422, description: 'Transición de estado inválida'),
        ]
    )]
    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $dto = ProjectDTO::fromArray($request->validated());
        $project = $this->projectService->updateProject($project, $dto);

        return $this->success(new ProjectResource($project));
    }

    #[OAT\Delete(
        path: '/projects/{project}',
        tags: ['Proyectos'],
        summary: 'Eliminar proyecto',
        description: 'Eliminación lógica (soft delete vía status)',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'project', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
        ],
        responses: [
            new OAT\Response(response: 200, description: 'Proyecto eliminado'),
            new OAT\Response(response: 422, description: 'Estado no permite eliminación'),
        ]
    )]
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projectService->delete($project);

        return $this->deleted('Project deleted successfully');
    }

    #[OAT\Post(
        path: '/projects/{project}/restore',
        tags: ['Proyectos'],
        summary: 'Restaurar proyecto',
        description: 'Restaura un proyecto eliminado',
        security: [['sanctum' => []]],
        parameters: [
            new OAT\Parameter(name: 'project', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
        ],
        responses: [
            new OAT\Response(response: 200, description: 'Proyecto restaurado'),
            new OAT\Response(response: 422, description: 'El proyecto no está eliminado'),
        ]
    )]
    public function restore(Project $project): JsonResponse
    {
        $this->authorize('restore', $project);

        $this->projectService->restore($project);

        return $this->success(null, 'Project restored successfully');
    }
}

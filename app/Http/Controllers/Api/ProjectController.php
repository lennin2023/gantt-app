<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ProjectDTO;
use App\Enums\ProjectStatusEnum;
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

class ProjectController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

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

    public function store(ProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $dto = ProjectDTO::fromArray($request->validated(), createdBy: Auth::id());
        $project = $this->projectService->createProject($dto);

        return $this->created(new ProjectResource($project));
    }

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

    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $dto = ProjectDTO::fromArray(
            array_merge($request->validated(), ['updated_by' => Auth::id()]),
            createdBy: $project->created_by,
        );

        $project = $this->projectService->updateProject($project, $dto);

        return $this->success(new ProjectResource($project));
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projectService->changeStatus($project, ProjectStatusEnum::ARCHIVED, Auth::id());

        return $this->deleted('Project archived successfully');
    }

    public function restore(Project $project): JsonResponse
    {
        $this->authorize('restore', $project);

        $this->projectService->changeStatus($project, ProjectStatusEnum::ACTIVE, Auth::id());

        return $this->success(null, 'Project restored successfully');
    }
}

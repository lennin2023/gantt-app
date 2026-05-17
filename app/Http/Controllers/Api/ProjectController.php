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
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless(Gate::allows('viewAny', Project::class), 403);

        $perPage = min((int) $request->query('per_page', 10), 100);

        $projects = $this->projectService->getUserProjects(
            userId: Auth::id(),
            perPage: $perPage
        );

        return ProjectResource::collection($projects);
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        abort_unless(Gate::allows('create', Project::class), 403);

        $dto = ProjectDTO::fromArray($request->validated(), createdBy: Auth::id());
        $project = $this->projectService->createProject($dto);

        return $this->created(new ProjectResource($project));
    }

    public function show(Project $project): JsonResponse
    {
        abort_unless(Gate::allows('view', $project), 403);

        $project->load(['tasks.dependencies', 'milestones']);

        return $this->success(new ProjectResource($project));
    }

    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        abort_unless(Gate::allows('update', $project), 403);

        $dto = ProjectDTO::fromArray(
            array_merge($request->validated(), ['updated_by' => Auth::id()]),
            createdBy: $project->created_by,
        );

        $project = $this->projectService->updateProject($project, $dto);

        return $this->success(new ProjectResource($project));
    }

    public function destroy(Project $project): JsonResponse
    {
        abort_unless(Gate::allows('delete', $project), 403);

        $this->projectService->deleteProject($project);

        return $this->deleted('Project deleted successfully');
    }

    public function restore(Project $project): JsonResponse
    {
        abort_unless(Gate::allows('restore', $project), 403);

        $this->projectService->restoreProject($project);

        return $this->success(null, 'Project restored successfully');
    }
}

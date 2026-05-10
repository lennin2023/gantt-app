<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ProjectDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProjectRequest;
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

        $dto = ProjectDTO::fromArray($request->validated(), Auth::id());
        $project = $this->projectService->createProject($dto);

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): ProjectResource
    {
        $project = $this->projectService->findById($id, ['tasks.dependencies', 'milestones']);

        abort_unless(Gate::allows('view', $project), 403);

        return new ProjectResource($project);
    }

    public function update(ProjectRequest $request, int $id): ProjectResource
    {
        $project = Project::findOrFail($id);

        abort_unless(Gate::allows('update', $project), 403);

        $dto = ProjectDTO::fromArray($request->validated(), Auth::id());
        $project = $this->projectService->updateProject($project, $dto);

        return new ProjectResource($project);
    }

    public function destroy(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        abort_unless(Gate::allows('delete', $project), 403);

        $this->projectService->deleteProject($project);

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }

    public function restore(int $id): JsonResponse
    {
        $project = Project::withTrashed()->findOrFail($id);

        abort_unless(Gate::allows('restore', $project), 403);

        $restored = $this->projectService->restoreProject($id);

        return response()->json([
            'message' => $restored ? 'Project restored successfully' : 'Failed to restore project',
        ]);
    }
}

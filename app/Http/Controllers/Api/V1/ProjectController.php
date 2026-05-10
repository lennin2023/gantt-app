<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ProjectDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $projects = $this->projectService->getUserProjects(
            userId: Auth::id(),
            perPage: 10
        );

        return ProjectResource::collection($projects);
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        $dto = ProjectDTO::fromArray($request->validated(), Auth::id());
        $project = $this->projectService->createProject($dto);

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): ProjectResource
    {
        $project = $this->projectService->findById($id, ['tasks.dependencies', 'milestones']);

        abort_unless($project->user_id === Auth::id(), 403);

        return new ProjectResource($project);
    }

    public function update(ProjectRequest $request, int $id): ProjectResource
    {
        $project = Project::findOrFail($id);

        abort_unless($project->user_id === Auth::id(), 403);

        $dto = ProjectDTO::fromArray($request->validated(), Auth::id());
        $project = $this->projectService->updateProject($project, $dto);

        return new ProjectResource($project);
    }

    public function destroy(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        abort_unless($project->user_id === Auth::id(), 403);

        $this->projectService->deleteProject($project);

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }
}

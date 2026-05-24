<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProjectUserRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\ProjectUserResource;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\User;
use App\Services\ProjectUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectUserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProjectUserService $projectUserService,
    ) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        abort_unless(Gate::allows('view', $project), 403);

        $projectUsers = $this->projectUserService->getProjectUsers($project->id);

        return ProjectUserResource::collection($projectUsers);
    }

    public function indexByRole(Project $project, ProjectRole $projectRole): AnonymousResourceCollection
    {
        abort_unless(Gate::allows('view', $project), 403);

        $projectUsers = $this->projectUserService->getProjectUsersByRole($project->id, $projectRole->id);

        return ProjectUserResource::collection($projectUsers);
    }

    public function store(ProjectUserRequest $request, Project $project): JsonResponse
    {
        abort_unless(Gate::allows('update', $project), 403);

        $validated = $request->validated();

        if ($this->projectUserService->userAlreadyAssigned($project->id, $validated['user_id'])) {
            return $this->validationError('User already in project');
        }

        $projectUser = $this->projectUserService->assignUser($project->id, $validated['user_id'], $validated['project_role_id'], Auth::id());

        return $this->created(new ProjectUserResource($projectUser));
    }

    public function destroy(Project $project, User $user): JsonResponse
    {
        abort_unless(Gate::allows('delete', $project), 403);

        $this->projectUserService->removeUser($project->id, $user->id, Auth::id());

        return $this->deleted('User removed from project');
    }
}

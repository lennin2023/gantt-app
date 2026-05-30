<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProjectUserRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\ProjectUserResource;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\ProjectUser;
use App\Models\User;
use App\Services\ProjectUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class ProjectUserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProjectUserService $projectUserService,
    ) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ProjectUser::class, $project]);

        $projectUsers = $this->projectUserService->getProjectUsers($project->id);

        return ProjectUserResource::collection($projectUsers);
    }

    public function indexByRole(Project $project, ProjectRole $projectRole): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ProjectUser::class, $project]);

        $projectUsers = $this->projectUserService->getProjectUsersByRole($project->id, $projectRole->id);

        return ProjectUserResource::collection($projectUsers);
    }

    public function store(ProjectUserRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [ProjectUser::class, $project]);

        $validated = $request->validated();

        $projectUser = $this->projectUserService->assignUser(
            projectId: $project->id,
            userId: $validated['user_id'],
            projectRoleId: $validated['project_role_id'],
            createdBy: Auth::id(),
        );

        return $this->created(new ProjectUserResource($projectUser));
    }

    public function destroy(Project $project, User $user): JsonResponse
    {
        $this->authorize('delete', [ProjectUser::class, $project]);

        $this->projectUserService->removeUser($project->id, $user->id);

        return $this->deleted('User removed from project');
    }
}

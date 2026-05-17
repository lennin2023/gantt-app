<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\ProjectUserResource;
use App\Models\Project;
use App\Services\ProjectUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectUserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProjectUserService $projectUserService,
    ) {}

    public function index(int $projectId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('view', $project), 403);

        $projectUsers = $this->projectUserService->getProjectUsers($projectId);

        return ProjectUserResource::collection($projectUsers);
    }

    public function indexByRole(int $projectId, int $projectRoleId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('view', $project), 403);

        $projectUsers = $this->projectUserService->getProjectUsersByRole($projectId, $projectRoleId);

        return ProjectUserResource::collection($projectUsers);
    }

    public function store(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('update', $project), 403);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_role_id' => 'required|exists:project_roles,id',
        ]);

        if ($this->projectUserService->userAlreadyAssigned($projectId, $validated['user_id'])) {
            return $this->validationError('User already in project');
        }

        $projectUser = $this->projectUserService->assignUser($projectId, $validated['user_id'], $validated['project_role_id'], Auth::id());

        return $this->created(new ProjectUserResource($projectUser));
    }

    public function destroy(int $projectId, int $userId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('delete', $project), 403);

        $this->projectUserService->removeUser($projectId, $userId);

        return $this->deleted('User removed from project');
    }
}

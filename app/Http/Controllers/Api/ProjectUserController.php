<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ProjectUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProjectUserRequest;
use App\Http\Resources\ProjectUserResource;
use App\Models\Project;
use App\Services\ProjectUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectUserController extends Controller
{
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

    public function store(ProjectUserRequest $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('update', $project), 403);

        if ($this->projectUserService->userAlreadyAssigned($projectId, $request->validated()['user_id'])) {
            return response()->json(['message' => 'User already in project'], 422);
        }

        $dto = ProjectUserDTO::fromArray([
            ...$request->validated(),
            'project_id' => $projectId,
            'created_by' => Auth::id(),
        ]);

        $projectUser = $this->projectUserService->assignUser($dto);

        return (new ProjectUserResource($projectUser))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(int $projectId, int $userId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('delete', $project), 403);

        $this->projectUserService->removeUser($projectId, $userId);

        return response()->json(['message' => 'User removed from project']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectUserResource;
use App\Models\Project;
use App\Models\ProjectUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectUserController extends Controller
{
    public function index(int $projectId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('view', $project), 403);

        $projectUsers = ProjectUser::with(['user', 'projectRole', 'creator'])
            ->where('project_id', $projectId)
            ->get();

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

        $existing = ProjectUser::where('project_id', $projectId)
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'User already in project'], 422);
        }

        $projectUser = ProjectUser::create([
            'project_id' => $projectId,
            'user_id' => $validated['user_id'],
            'project_role_id' => $validated['project_role_id'],
            'created_by' => Auth::id(),
        ]);

        $projectUser->load(['user', 'projectRole', 'creator']);

        return (new ProjectUserResource($projectUser))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(int $projectId, int $userId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('delete', $project), 403);

        $projectUser = ProjectUser::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $projectUser->delete();

        return response()->json(['message' => 'User removed from project']);
    }
}

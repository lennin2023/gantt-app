<?php

namespace App\Http\Controllers\Api;

use App\DTOs\MilestoneDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MilestoneRequest;
use App\Http\Resources\MilestoneResource;
use App\Models\Milestone;
use App\Models\Project;
use App\Services\MilestoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MilestoneController extends Controller
{
    public function __construct(
        private readonly MilestoneService $milestoneService,
    ) {}

    public function index(Request $request, int $projectId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('view', $project), 403);

        $perPage = min((int) $request->query('per_page', 10), 100);

        $milestones = $this->milestoneService->getProjectMilestones($projectId, $perPage);

        return MilestoneResource::collection($milestones);
    }

    public function store(MilestoneRequest $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('create', $project), 403);

        $dto = MilestoneDTO::fromArray(
            array_merge($request->validated(), ['created_by' => Auth::id()]),
            $projectId
        );

        $milestone = $this->milestoneService->createMilestone($dto);

        return (new MilestoneResource($milestone))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $projectId, int $milestoneId): MilestoneResource
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('view', $project), 403);

        $milestone = Milestone::where('project_id', $projectId)->findOrFail($milestoneId);

        return new MilestoneResource($milestone);
    }

    public function update(MilestoneRequest $request, int $projectId, int $milestoneId): MilestoneResource
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('update', $project), 403);

        $milestone = Milestone::where('project_id', $projectId)->findOrFail($milestoneId);

        $dto = MilestoneDTO::fromArray(
            array_merge($request->validated(), ['updated_by' => Auth::id()]),
            $projectId
        );

        $milestone = $this->milestoneService->updateMilestone($milestone, $dto);

        return new MilestoneResource($milestone);
    }

    public function destroy(int $projectId, int $milestoneId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('delete', $project), 403);

        $milestone = Milestone::where('project_id', $projectId)->findOrFail($milestoneId);

        $this->milestoneService->deleteMilestone($milestone);

        return response()->json(['message' => 'Milestone deleted successfully']);
    }

    public function restore(int $projectId, int $milestoneId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('restore', $project), 403);

        $milestone = Milestone::withTrashed()->where('project_id', $projectId)->findOrFail($milestoneId);

        $this->milestoneService->restoreMilestone($milestone);

        return response()->json(['message' => 'Milestone restored successfully']);
    }
}

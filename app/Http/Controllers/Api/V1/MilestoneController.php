<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\MilestoneDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MilestoneRequest;
use App\Http\Resources\MilestoneResource;
use App\Models\Milestone;
use App\Models\Project;
use App\Services\MilestoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class MilestoneController extends Controller
{
    public function __construct(
        private readonly MilestoneService $milestoneService,
    ) {}

    public function index(int $projectId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('view', $project), 403);

        $milestones = $this->milestoneService->getProjectMilestones($projectId);

        return MilestoneResource::collection($milestones);
    }

    public function store(MilestoneRequest $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        abort_unless(Gate::allows('create', $project), 403);

        $dto = MilestoneDTO::fromArray($request->validated(), $projectId);
        $milestone = $this->milestoneService->createMilestone($dto);

        return (new MilestoneResource($milestone))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $projectId, int $milestoneId): MilestoneResource
    {
        $project = Project::findOrFail($projectId);
        $milestone = $this->milestoneService->findById($milestoneId);

        abort_unless($milestone && Gate::allows('view', $project), 403);

        return new MilestoneResource($milestone);
    }

    public function update(MilestoneRequest $request, int $projectId, int $milestoneId): MilestoneResource
    {
        $project = Project::findOrFail($projectId);
        $milestone = Milestone::where('project_id', $projectId)->findOrFail($milestoneId);

        abort_unless(Gate::allows('update', $project), 403);

        $dto = MilestoneDTO::fromArray($request->validated(), $projectId);
        $milestone = $this->milestoneService->updateMilestone($milestone, $dto);

        return new MilestoneResource($milestone);
    }

    public function destroy(int $projectId, int $milestoneId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $milestone = Milestone::where('project_id', $projectId)->findOrFail($milestoneId);

        abort_unless(Gate::allows('delete', $project), 403);

        $this->milestoneService->deleteMilestone($milestone);

        return response()->json([
            'message' => 'Milestone deleted successfully',
        ]);
    }
}

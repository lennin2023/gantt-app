<?php

namespace App\Http\Controllers\Api;

use App\DTOs\MilestoneDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MilestoneRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\MilestoneResource;
use App\Models\Milestone;
use App\Models\Project;
use App\Services\MilestoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MilestoneController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MilestoneService $milestoneService,
    ) {}

    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Milestone::class, $project]);

        $perPage = min((int) $request->query('per_page', 10), 100);
        $filters = [];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        $milestones = $this->milestoneService->getProjectMilestones($project->id, $perPage, $filters);

        return MilestoneResource::collection($milestones);
    }

    public function store(MilestoneRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Milestone::class, $project]);

        $dto = MilestoneDTO::fromArray($request->validated(), $project->id);
        $milestone = $this->milestoneService->createMilestone($dto);

        return $this->created(new MilestoneResource($milestone));
    }

    public function show(Milestone $milestone): JsonResponse
    {
        $milestone->loadMissing('project');
        $this->authorize('view', [Milestone::class, $milestone->project]);

        return $this->success(new MilestoneResource($milestone));
    }

    public function update(MilestoneRequest $request, Milestone $milestone): JsonResponse
    {
        $milestone->loadMissing('project');
        $this->authorize('update', [Milestone::class, $milestone->project]);

        $dto = MilestoneDTO::fromArray($request->validated(), $milestone->project_id);
        $milestone = $this->milestoneService->updateMilestone($milestone, $dto);

        return $this->success(new MilestoneResource($milestone));
    }

    public function destroy(Milestone $milestone): JsonResponse
    {
        $milestone->loadMissing('project');
        $this->authorize('delete', [Milestone::class, $milestone->project]);

        $this->milestoneService->deactivate($milestone);

        return $this->deleted('Milestone deactivated successfully');
    }

    public function restore(Milestone $milestone): JsonResponse
    {
        $milestone->loadMissing('project');
        $this->authorize('restore', [Milestone::class, $milestone->project]);

        $this->milestoneService->activate($milestone);

        return $this->success(null, 'Milestone activated successfully');
    }
}

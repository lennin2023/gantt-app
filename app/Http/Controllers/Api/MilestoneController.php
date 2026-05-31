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
use Illuminate\Support\Facades\Auth;

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

        $dto = MilestoneDTO::fromArray(
            array_merge($request->validated(), ['created_by' => Auth::id()]),
            $project->id
        );

        $milestone = $this->milestoneService->createMilestone($dto);

        return $this->created(new MilestoneResource($milestone));
    }

    public function show(Project $project, Milestone $milestone): JsonResponse
    {
        $this->authorize('view', [Milestone::class, $project]);
        abort_if($milestone->project_id !== $project->id, 404);

        return $this->success(new MilestoneResource($milestone));
    }

    public function update(MilestoneRequest $request, Project $project, Milestone $milestone): JsonResponse
    {
        $this->authorize('update', [Milestone::class, $project]);
        abort_if($milestone->project_id !== $project->id, 404);

        $dto = MilestoneDTO::fromArray(
            array_merge($request->validated(), ['updated_by' => Auth::id()]),
            $project->id
        );

        $milestone = $this->milestoneService->updateMilestone($milestone, $dto);

        return $this->success(new MilestoneResource($milestone));
    }

    public function destroy(Project $project, Milestone $milestone): JsonResponse
    {
        $this->authorize('delete', [Milestone::class, $project]);
        abort_if($milestone->project_id !== $project->id, 404);

        $this->milestoneService->deactivate($milestone, Auth::id());

        return $this->deleted('Milestone deactivated successfully');
    }

    public function restore(Project $project, Milestone $milestone): JsonResponse
    {
        $this->authorize('restore', [Milestone::class, $project]);
        abort_if($milestone->project_id !== $project->id, 404);

        $this->milestoneService->activate($milestone, Auth::id());

        return $this->success(null, 'Milestone activated successfully');
    }
}

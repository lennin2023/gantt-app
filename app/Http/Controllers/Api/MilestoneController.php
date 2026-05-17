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
use Illuminate\Support\Facades\Gate;

class MilestoneController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MilestoneService $milestoneService,
    ) {}

    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        abort_unless(Gate::allows('view', $project), 403);

        $perPage = min((int) $request->query('per_page', 10), 100);

        $milestones = $this->milestoneService->getProjectMilestones($project->id, $perPage);

        return MilestoneResource::collection($milestones);
    }

    public function store(MilestoneRequest $request, Project $project): JsonResponse
    {
        abort_unless(Gate::allows('create', $project), 403);

        $dto = MilestoneDTO::fromArray(
            array_merge($request->validated(), ['created_by' => Auth::id()]),
            $project->id
        );

        $milestone = $this->milestoneService->createMilestone($dto);

        return $this->created(new MilestoneResource($milestone));
    }

    public function show(Project $project, Milestone $milestone): JsonResponse
    {
        abort_unless(Gate::allows('view', $project), 403);

        return $this->success(new MilestoneResource($milestone));
    }

    public function update(MilestoneRequest $request, Project $project, Milestone $milestone): JsonResponse
    {
        abort_unless(Gate::allows('update', $project), 403);

        $dto = MilestoneDTO::fromArray(
            array_merge($request->validated(), ['updated_by' => Auth::id()]),
            $project->id
        );

        $milestone = $this->milestoneService->updateMilestone($milestone, $dto);

        return $this->success(new MilestoneResource($milestone));
    }

    public function destroy(Project $project, Milestone $milestone): JsonResponse
    {
        abort_unless(Gate::allows('delete', $project), 403);

        $this->milestoneService->deleteMilestone($milestone);

        return $this->deleted('Milestone deleted successfully');
    }

    public function restore(Project $project, Milestone $milestone): JsonResponse
    {
        abort_unless(Gate::allows('restore', $project), 403);

        $this->milestoneService->restoreMilestone($milestone);

        return $this->success(null, 'Milestone restored successfully');
    }
}

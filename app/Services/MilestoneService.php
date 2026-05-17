<?php

namespace App\Services;

use App\DTOs\MilestoneDTO;
use App\Events\MilestoneCreated;
use App\Events\MilestoneDeleted;
use App\Events\MilestoneRestored;
use App\Events\MilestoneUpdated;
use App\Models\Milestone;
use App\Repositories\Contracts\MilestoneRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MilestoneService
{
    public function __construct(
        private readonly MilestoneRepositoryInterface $milestoneRepository,
    ) {}

    public function getProjectMilestones(int $projectId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->milestoneRepository->getAllByProject($projectId, $perPage);
    }

    public function findById(int $id): ?Milestone
    {
        return $this->milestoneRepository->findById($id);
    }

    public function createMilestone(MilestoneDTO $dto): Milestone
    {
        return DB::transaction(function () use ($dto) {
            $milestone = $this->milestoneRepository->create($dto->toArray());
            MilestoneCreated::dispatch($milestone);

            return $milestone;
        });
    }

    public function updateMilestone(Milestone $milestone, MilestoneDTO $dto): Milestone
    {
        return DB::transaction(function () use ($milestone, $dto) {
            $milestone = $this->milestoneRepository->update($milestone, $dto->toArray());
            MilestoneUpdated::dispatch($milestone);

            return $milestone;
        });
    }

    public function deleteMilestone(Milestone $milestone): bool
    {
        return DB::transaction(function () use ($milestone) {
            MilestoneDeleted::dispatch($milestone);

            return $this->milestoneRepository->delete($milestone);
        });
    }

    public function restoreMilestone(Milestone $milestone): bool
    {
        return DB::transaction(function () use ($milestone) {
            $result = $this->milestoneRepository->restore($milestone);
            MilestoneRestored::dispatch($milestone);

            return $result;
        });
    }
}

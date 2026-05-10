<?php

namespace App\Services;

use App\DTOs\MilestoneDTO;
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
            return $this->milestoneRepository->create($dto->toArray());
        });
    }

    public function updateMilestone(Milestone $milestone, MilestoneDTO $dto): Milestone
    {
        return DB::transaction(function () use ($milestone, $dto) {
            return $this->milestoneRepository->update($milestone, $dto->toArray());
        });
    }

    public function deleteMilestone(Milestone $milestone): bool
    {
        return DB::transaction(function () use ($milestone) {
            return $this->milestoneRepository->delete($milestone);
        });
    }

    public function restoreMilestone(int $id): bool
    {
        return $this->milestoneRepository->restore($id);
    }
}

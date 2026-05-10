<?php

namespace App\Services;

use App\DTOs\MilestoneDTO;
use App\Models\Milestone;
use App\Repositories\Contracts\MilestoneRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

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
        return $this->milestoneRepository->create($dto->toArray());
    }

    public function updateMilestone(Milestone $milestone, MilestoneDTO $dto): Milestone
    {
        return $this->milestoneRepository->update($milestone, $dto->toArray());
    }

    public function deleteMilestone(Milestone $milestone): bool
    {
        return $this->milestoneRepository->delete($milestone);
    }

    public function restoreMilestone(int $id): bool
    {
        return $this->milestoneRepository->restore($id);
    }
}

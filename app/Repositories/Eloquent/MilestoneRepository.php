<?php

namespace App\Repositories\Eloquent;

use App\Models\Milestone;
use App\Repositories\Contracts\MilestoneRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MilestoneRepository implements MilestoneRepositoryInterface
{
    public function getAllByProject(int $projectId, int $perPage = 10): LengthAwarePaginator
    {
        return Milestone::with(['creator', 'updater'])
            ->where('project_id', $projectId)
            ->orderBy('date')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Milestone
    {
        return Milestone::with('project')->find($id);
    }

    public function create(array $data): Milestone
    {
        return Milestone::create($data);
    }

    public function update(Milestone $milestone, array $data): Milestone
    {
        $milestone->update($data);

        return $milestone->fresh();
    }

    public function delete(Milestone $milestone): void
    {
        $milestone->delete();
    }

    public function restore(Milestone $milestone): void
    {
        $milestone->restore();
    }
}

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
            ->where('is_active', true)
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

    public function toggleActive(Milestone $milestone, int $userId): Milestone
    {
        $milestone->is_active = ! $milestone->is_active;
        $milestone->updated_by = $userId;
        $milestone->save();

        return $milestone;
    }

    public function deactivate(Milestone $milestone, int $userId): void
    {
        $milestone->is_active = false;
        $milestone->updated_by = $userId;
        $milestone->save();
    }

    public function activate(Milestone $milestone, int $userId): void
    {
        $milestone->is_active = true;
        $milestone->updated_by = $userId;
        $milestone->save();
    }
}

<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function getAllByUser(int $userId, int $perPage = 10, ?int $statusId = null): LengthAwarePaginator
    {
        return Project::with('status')
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->orWhereIn('id', ProjectUser::where('user_id', $userId)->select('project_id'));
            })
            ->when($statusId, fn ($q) => $q->where('project_status_id', $statusId))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findById(int $id, array $with = []): ?Project
    {
        return Project::with($with)->find($id);
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh();
    }
}

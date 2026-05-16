<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function getAllByUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Project::with('status')
            ->where('created_by', $userId)
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

    public function delete(Project $project): bool
    {
        return $project->delete();
    }

    public function restore(Project $project): bool
    {
        return $project->restore();
    }
}

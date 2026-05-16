<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    public function getAllByUser(int $userId, int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id, array $with = []): ?Project;

    public function create(array $data): Project;

    public function update(Project $project, array $data): Project;

    public function delete(Project $project): bool;

    public function restore(Project $project): bool;
}

<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    public function getAllByProject(int $projectId, int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Task;

    public function create(array $data): Task;

    public function update(Task $task, array $data): Task;

    public function delete(Task $task): bool;

    public function syncDependencies(Task $task, array $dependencyIds): void;

    public function wouldCreateCycle(Task $task, int $newDependencyId): bool;

    public function restore(Task $task): bool;
}

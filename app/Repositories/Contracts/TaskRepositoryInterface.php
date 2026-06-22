<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TaskRepositoryInterface
{
    public function getAllByProject(int $projectId, int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Task;

    public function create(array $data): Task;

    public function update(Task $task, array $data): Task;

    public function syncDependencies(Task $task, array $dependencyIds, string $type): void;

    public function wouldCreateCycle(Task $task, int $newDependencyId): bool;

    public function findByIds(array $ids): Collection;

    public function getDescendantsByPath(string $path): Collection;

    public function getActiveRootTasks(int $projectId): Collection;

    public function getChildrenForProgressCalc(int $parentId): Collection;
}

<?php

namespace App\Repositories\Contracts;

use App\Models\Milestone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MilestoneRepositoryInterface
{
    public function getAllByProject(int $projectId, int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Milestone;

    public function create(array $data): Milestone;

    public function update(Milestone $milestone, array $data): Milestone;

    public function toggleActive(Milestone $milestone, int $userId): Milestone;

    public function deactivate(Milestone $milestone, int $userId): void;

    public function activate(Milestone $milestone, int $userId): void;
}

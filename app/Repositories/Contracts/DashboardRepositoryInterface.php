<?php

namespace App\Repositories\Contracts;

interface DashboardRepositoryInterface
{
    public function getMetrics(int $userId): array;

    public function getProjects(int $userId, int $limit = 10): array;
}

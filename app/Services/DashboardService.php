<?php

namespace App\Services;

use App\Repositories\Contracts\DashboardRepositoryInterface;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
    ) {}

    public function getStats(int $userId): array
    {
        return [
            'metrics' => $this->dashboardRepository->getMetrics($userId),
            'projects' => $this->dashboardRepository->getProjects($userId),
        ];
    }
}

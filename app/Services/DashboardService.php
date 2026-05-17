<?php

namespace App\Services;

use App\DTOs\DashboardDTO;
use App\Repositories\Contracts\DashboardRepositoryInterface;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
    ) {}

    public function getStats(int $userId): DashboardDTO
    {
        return DashboardDTO::fromArray([
            'metrics' => $this->dashboardRepository->getMetrics($userId),
            'projects' => $this->dashboardRepository->getProjects($userId),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function stats(): JsonResponse
    {
        $this->authorize('viewDashboard');

        $stats = $this->dashboardService->getStats(Auth::id());

        return $this->success($stats->toArray());
    }
}

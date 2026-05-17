<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function stats(): JsonResponse
    {
        abort_unless(Gate::allows('viewDashboard'), 403);

        $stats = $this->dashboardService->getStats(Auth::id());

        return response()->json($stats);
    }
}

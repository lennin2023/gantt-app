<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OAT;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    #[OAT\Get(
        path: '/dashboard/stats',
        tags: ['Dashboard'],
        summary: 'Estadísticas del dashboard',
        description: 'Métricas agregadas del usuario autenticado',
        security: [['sanctum' => []]],
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Estadísticas del dashboard',
                content: new OAT\JsonContent(
                    properties: [
                        new OAT\Property(property: 'data', type: 'object',
                            properties: [
                                new OAT\Property(property: 'metrics', type: 'object'),
                                new OAT\Property(property: 'projects', type: 'array',
                                    items: new OAT\Items(type: 'object')
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OAT\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function stats(): JsonResponse
    {
        $this->authorize('viewDashboard');

        $stats = $this->dashboardService->getStats(Auth::id());

        return $this->success($stats->toArray());
    }
}

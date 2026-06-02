<?php

use App\Exceptions\BulkOperationException;
use App\Exceptions\CycleDetectionException;
use App\Exceptions\ProjectAlreadyInStatusException;
use App\Exceptions\ProjectUserAlreadyAssignedException;
use App\Exceptions\ProjectUserNotFoundException;
use App\Exceptions\TaskAlreadyInStatusException;
use App\Exceptions\TaskNotCancelledException;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', EnsureFrontendRequestsAreStateful::class, ForceJsonResponse::class])
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => CheckRole::class,
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $_e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => __('exceptions.not_found'),
                ], 404);
            }
        });

        $exceptions->render(function (ModelNotFoundException $_e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => __('exceptions.not_found'),
                ], 404);
            }
        });
        $exceptions->render(function (ProjectAlreadyInStatusException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (ProjectUserAlreadyAssignedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (ProjectUserNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (CycleDetectionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (BulkOperationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (TaskAlreadyInStatusException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (TaskNotCancelledException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
    })->create();

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\MilestoneController;

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:web')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::apiResource('projects', ProjectController::class);

        Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
        Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
        Route::get('/tasks/{task}', [TaskController::class, 'show']);
        Route::put('/tasks/{task}', [TaskController::class, 'update']);
        Route::patch('/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
        Route::patch('/tasks/bulk', [TaskController::class, 'bulkUpdate']);
        Route::delete('/tasks/bulk', [TaskController::class, 'bulkDelete']);

        Route::get('/projects/{project}/milestones', [MilestoneController::class, 'index']);
        Route::post('/projects/{project}/milestones', [MilestoneController::class, 'store']);
        Route::get('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'show']);
        Route::put('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'update']);
        Route::patch('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'update']);
        Route::delete('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'destroy']);
    });
});

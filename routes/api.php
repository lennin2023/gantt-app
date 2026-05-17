<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MilestoneController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectUserController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', 'throttle:api'])->group(function () {
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    Route::apiResource('projects', ProjectController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('/projects/{project}/restore', [ProjectController::class, 'restore']);

    Route::get('/projects/{project}/users', [ProjectUserController::class, 'index']);
    Route::get('/projects/{project}/users/role/{projectRole}', [ProjectUserController::class, 'indexByRole']);
    Route::post('/projects/{project}/users', [ProjectUserController::class, 'store']);
    Route::delete('/projects/{project}/users/{user}', [ProjectUserController::class, 'destroy']);

    Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::post('/tasks/{task}/restore', [TaskController::class, 'restore']);
    Route::patch('/tasks/bulk', [TaskController::class, 'bulkUpdate']);
    Route::delete('/tasks/bulk', [TaskController::class, 'bulkDelete']);

    Route::get('/projects/{project}/milestones', [MilestoneController::class, 'index']);
    Route::post('/projects/{project}/milestones', [MilestoneController::class, 'store']);
    Route::get('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'show']);
    Route::put('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'update']);
    Route::patch('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'update']);
    Route::delete('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'destroy']);
    Route::post('/projects/{project}/milestones/{milestone}/restore', [MilestoneController::class, 'restore']);
});

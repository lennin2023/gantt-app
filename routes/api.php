<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\MilestoneController;

Route::middleware(['auth:web', 'throttle:api'])->group(function () {
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

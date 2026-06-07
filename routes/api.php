<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MilestoneController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectUserController;
use App\Http\Controllers\Api\TaskAssignmentController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:login');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
        Route::patch('/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
        Route::post('/{project}/restore', [ProjectController::class, 'restore'])->name('restore');

        Route::prefix('/{project}/users')->name('users.')->group(function () {
            Route::get('/', [ProjectUserController::class, 'index'])->name('index');
            Route::get('/role/{projectRole}', [ProjectUserController::class, 'indexByRole'])->name('index-by-role');
            Route::post('/', [ProjectUserController::class, 'store'])->name('store');
            Route::delete('/{user}', [ProjectUserController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('/{project}/milestones')->name('milestones.')->group(function () {
            Route::get('/', [MilestoneController::class, 'index'])->name('index');
            Route::post('/', [MilestoneController::class, 'store'])->name('store');
        });

        Route::prefix('/{project}/tasks')->name('tasks.')->group(function () {
            Route::get('/', [TaskController::class, 'index'])->name('index');
            Route::post('/', [TaskController::class, 'store'])->name('store');
        });
    });

    Route::prefix('milestones')->name('milestones.')->group(function () {
        Route::get('/{milestone}', [MilestoneController::class, 'show'])->name('show');
        Route::patch('/{milestone}', [MilestoneController::class, 'update'])->name('update');
        Route::delete('/{milestone}', [MilestoneController::class, 'destroy'])->name('destroy');
        Route::post('/{milestone}/restore', [MilestoneController::class, 'restore'])->name('restore');
    });

    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::patch('/bulk-update', [TaskController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/{task}', [TaskController::class, 'show'])->name('show');
        Route::patch('/{task}', [TaskController::class, 'update'])->name('update');
        Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
        Route::post('/{task}/restore', [TaskController::class, 'restore'])->name('restore');

        Route::prefix('/{task}/assignments')->name('assignments.')->group(function () {
            Route::get('/', [TaskAssignmentController::class, 'index'])->name('index');
            Route::post('/', [TaskAssignmentController::class, 'store'])->name('store');
            Route::patch('/{assignment}', [TaskAssignmentController::class, 'update'])->name('update');
            Route::delete('/{assignment}', [TaskAssignmentController::class, 'destroy'])->name('destroy');
        });
    });
});

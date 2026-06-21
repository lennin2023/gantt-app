<?php

use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['role_id' => 1]);
    $this->actingAs($this->user, 'sanctum');

    $this->project = Project::factory()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->user->id,
    ]);
});

// ─── Paths ────────────────────────────────────────────────────────────────

it('assigns correct padded path on creation', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    expect($container->fresh()->path)->toBe('0001');
});

it('assigns correct nested path', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'created_by' => $this->user->id,
    ]);

    expect($task->fresh()->path)->toBe('0001/0001');
});

it('generates correct display_path', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $sub = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $sub->id,
        'type' => TaskTypeEnum::TASK->value,
        'created_by' => $this->user->id,
    ]);

    expect($task->fresh()->getDisplayPath())->toBe('1.1.1');
});

it('sorts siblings correctly with padded paths', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    // Crear 13 tareas para verificar que 0013 va después de 0002
    for ($i = 0; $i < 13; $i++) {
        Task::factory()->create([
            'project_id' => $this->project->id,
            'parent_id' => $container->id,
            'type' => TaskTypeEnum::TASK->value,
            'created_by' => $this->user->id,
        ]);
    }

    $tasks = Task::where('parent_id', $container->id)
        ->orderBy('path')
        ->get();

    expect($tasks->first()->path)->toBe('0001/0001')
        ->and($tasks->last()->path)->toBe('0001/0013');
});

// ─── Progreso del container ───────────────────────────────────────────────

it('recalculates container progress when task progress changes', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $task1 = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'progress' => 100,
        'created_by' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::PENDING->value,
        'progress' => 0,
        'created_by' => $this->user->id,
    ]);

    $this->patchJson("/api/tasks/{$task2->id}", ['progress' => 50]);

    expect($container->fresh()->progress)->toBe(75);
});

it('recalculates container status when all tasks complete', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'progress' => 0,
        'created_by' => $this->user->id,
    ]);

    $this->patchJson("/api/tasks/{$task->id}", [
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
        'progress' => 100,
    ]);

    expect($container->fresh()->task_status_id)->toBe(TaskStatusEnum::COMPLETED->value);
});

it('propagates progress from subcontainer to root container', function () {
    $root = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $sub = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $root->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $sub->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'progress' => 0,
        'created_by' => $this->user->id,
    ]);

    $this->patchJson("/api/tasks/{$task->id}", ['progress' => 100]);

    expect($sub->fresh()->progress)->toBe(100)
        ->and($root->fresh()->progress)->toBe(100);
});

// ─── Clear cuando container queda vacío ───────────────────────────────────

it('clears container when last task is deleted', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::PENDING->value,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'progress' => 50,
        'created_by' => $this->user->id,
    ]);

    $this->deleteJson("/api/tasks/{$task->id}");

    $freshContainer = $container->fresh();
    expect($freshContainer->progress)->toBe(0)
        ->and($freshContainer->task_status_id)->toBe(TaskStatusEnum::PENDING->value)
        ->and($freshContainer->start_date)->toBeNull()
        ->and($freshContainer->end_date)->toBeNull();
});

// ─── Cascada destroy/restore ──────────────────────────────────────────────

it('cascades DELETED status to children on destroy', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $pendingTask = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::PENDING->value,
        'created_by' => $this->user->id,
    ]);

    $completedTask = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
        'created_by' => $this->user->id,
    ]);

    $this->deleteJson("/api/tasks/{$container->id}");

    expect($container->fresh()->task_status_id)->toBe(TaskStatusEnum::DELETED->value)
        ->and($pendingTask->fresh()->task_status_id)->toBe(TaskStatusEnum::DELETED->value)
        ->and($completedTask->fresh()->task_status_id)->toBe(TaskStatusEnum::COMPLETED->value); // no se toca
});

it('cascades PENDING status to DELETED children on restore', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'task_status_id' => TaskStatusEnum::DELETED->value,
        'created_by' => $this->user->id,
    ]);

    $deletedTask = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::DELETED->value,
        'created_by' => $this->user->id,
    ]);

    $completedTask = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
        'created_by' => $this->user->id,
    ]);

    $this->postJson("/api/tasks/{$container->id}/restore");

    expect($container->fresh()->task_status_id)->toBe(TaskStatusEnum::PENDING->value)
        ->and($deletedTask->fresh()->task_status_id)->toBe(TaskStatusEnum::PENDING->value)
        ->and($completedTask->fresh()->task_status_id)->toBe(TaskStatusEnum::COMPLETED->value);
});

// ─── Fechas del proyecto ──────────────────────────────────────────────────

it('updates project dates when root task is created with dates', function () {
    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::TASK->value,
        'title' => 'Root task',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $project = $this->project->fresh();
    expect($project->start_date->toDateString())->toBe('2026-01-01')
        ->and($project->end_date->toDateString())->toBe('2026-12-31');
});

it('updates project dates based on min/max of root tasks', function () {
    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::TASK->value,
        'title' => 'Task A',
        'start_date' => '2026-03-01',
        'end_date' => '2026-06-30',
    ]);

    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::TASK->value,
        'title' => 'Task B',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $project = $this->project->fresh();
    expect($project->start_date->toDateString())->toBe('2026-01-01')
        ->and($project->end_date->toDateString())->toBe('2026-12-31');
});

// ─── Validaciones de jerarquía ────────────────────────────────────────────

it('rejects task with non-container parent', function () {
    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::TASK->value,
        'created_by' => $this->user->id,
    ]);

    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::TASK->value,
        'title' => 'Child task',
        'parent_id' => $task->id,
    ])->assertStatus(422);
});

it('rejects task with parent from different project', function () {
    $otherProject = Project::factory()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->user->id,
    ]);

    $container = Task::factory()->create([
        'project_id' => $otherProject->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::TASK->value,
        'title' => 'Task',
        'parent_id' => $container->id,
    ])->assertStatus(422);
});

it('rejects self-parent', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $this->patchJson("/api/tasks/{$container->id}", [
        'parent_id' => $container->id,
    ])->assertStatus(422);
});

it('rejects container with task_status_id', function () {
    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::CONTAINER->value,
        'title' => 'Container',
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
    ])->assertStatus(422);
});

it('rejects container with progress', function () {
    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::CONTAINER->value,
        'title' => 'Container',
        'progress' => 50,
    ])->assertStatus(422);
});

it('rejects milestone with description', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::MILESTONE->value,
        'title' => 'Milestone',
        'parent_id' => $container->id,
        'start_date' => '2026-06-30',
        'description' => 'No debería aceptarse',
    ])->assertStatus(422);
});

it('auto-syncs milestone end_date to start_date', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    $milestone = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::MILESTONE->value,
        'task_status_id' => TaskStatusEnum::PENDING->value,
        'start_date' => '2026-06-30',
        'created_by' => $this->user->id,
    ]);

    $this->patchJson("/api/tasks/{$milestone->id}", [
        'start_date' => '2026-09-15',
    ]);

    $fresh = $milestone->fresh();
    expect($fresh->start_date->toDateString())->toBe('2026-09-15')
        ->and($fresh->end_date->toDateString())->toBe('2026-09-15');
});

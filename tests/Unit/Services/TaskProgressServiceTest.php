<?php

use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TaskProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['role_id' => 1]);
    $this->project = Project::factory()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->user->id,
    ]);

    $this->service = app(TaskProgressService::class);
});

// ─── recalculate() ─────────────────────────────────────────────────────────

it('returns false for non-container task', function () {
    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::TASK->value,
        'created_by' => $this->user->id,
    ]);

    expect($this->service->recalculate($task))->toBeFalse();
});

it('returns false for deleted container', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'task_status_id' => TaskStatusEnum::DELETED->value,
        'created_by' => $this->user->id,
    ]);

    expect($this->service->recalculate($container))->toBeFalse();
});

it('clears container when no active children exist', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'progress' => 50,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'created_by' => $this->user->id,
    ]);

    expect($this->service->recalculate($container))->toBeTrue()
        ->and($container->progress)->toBe(0)
        ->and($container->task_status_id)->toBe(TaskStatusEnum::PENDING->value)
        ->and($container->start_date)->toBeNull()
        ->and($container->end_date)->toBeNull();
});

it('calculates average progress from children', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'progress' => 30,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'progress' => 70,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->progress)->toBe(50);
});

it('excludes cancelled children from progress calculation', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'progress' => 50,
        'task_status_id' => TaskStatusEnum::CANCELLED->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'progress' => 80,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->progress)->toBe(80);
});

it('excludes deleted children from progress calculation', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'progress' => 100,
        'task_status_id' => TaskStatusEnum::DELETED->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'progress' => 40,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->progress)->toBe(40);
});

it('excludes milestones from progress calculation', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'progress' => 60,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::MILESTONE->value,
        'progress' => 100,
        'start_date' => '2026-06-30',
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->progress)->toBe(60);
});

it('returns false when container values have not changed', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'progress' => 50,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'progress' => 50,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'created_by' => $this->user->id,
    ]);

    expect($this->service->recalculate($container))->toBeFalse();
});

// ─── calculateStatus scenarios ─────────────────────────────────────────────

it('clears container when all children are cancelled', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'progress' => 50,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::CANCELLED->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::CANCELLED->value,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->task_status_id)->toBe(TaskStatusEnum::PENDING->value)
        ->and($container->progress)->toBe(0);
});

it('clears container when all children are deleted', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'progress' => 50,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::DELETED->value,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->task_status_id)->toBe(TaskStatusEnum::PENDING->value)
        ->and($container->progress)->toBe(0);
});

it('sets status to COMPLETED when all active children are completed', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
        'progress' => 100,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
        'progress' => 100,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->task_status_id)->toBe(TaskStatusEnum::COMPLETED->value)
        ->and($container->progress)->toBe(100);
});

it('sets status to IN_PROGRESS when any child is in progress', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
        'progress' => 100,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'progress' => 50,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->task_status_id)->toBe(TaskStatusEnum::IN_PROGRESS->value);
});

it('sets status to IN_PROGRESS when any child is on hold', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
        'progress' => 100,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::ON_HOLD->value,
        'progress' => 20,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->task_status_id)->toBe(TaskStatusEnum::IN_PROGRESS->value);
});

it('sets status to IN_PROGRESS when some children are completed but not all', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
        'progress' => 100,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::PENDING->value,
        'progress' => 0,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->task_status_id)->toBe(TaskStatusEnum::IN_PROGRESS->value);
});

it('sets status to PENDING when all active children are pending', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::PENDING->value,
        'progress' => 0,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->task_status_id)->toBe(TaskStatusEnum::PENDING->value);
});

// ─── Date calculations ─────────────────────────────────────────────────────

it('calculates start_date as minimum of children start dates', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'start_date' => '2026-03-01',
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'start_date' => '2026-01-15',
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->start_date->toDateString())->toBe('2026-01-15');
});

it('calculates end_date as maximum of children end dates', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'end_date' => '2026-06-30',
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'end_date' => '2026-12-31',
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->end_date->toDateString())->toBe('2026-12-31');
});

it('sets dates to null when children have no dates', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'start_date' => null,
        'end_date' => null,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculate($container);

    expect($container->start_date)->toBeNull()
        ->and($container->end_date)->toBeNull();
});

// ─── recalculateAncestors() ────────────────────────────────────────────────

it('does nothing for root task with no parent', function () {
    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::TASK->value,
        'created_by' => $this->user->id,
    ]);

    $projectService = app(ProjectService::class);

    expect($this->service->recalculateAncestors($task))->toBeNull();
});

it('recalculates parent container when task changes', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'progress' => 0,
        'created_by' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'progress' => 100,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculateAncestors($task);

    expect($container->fresh()->progress)->toBe(100);
});

it('recalculates through multiple hierarchy levels', function () {
    $root = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'progress' => 0,
        'created_by' => $this->user->id,
    ]);

    $sub = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $root->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'progress' => 0,
        'created_by' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $sub->id,
        'type' => TaskTypeEnum::TASK->value,
        'progress' => 80,
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculateAncestors($task);

    expect($sub->fresh()->progress)->toBe(80)
        ->and($root->fresh()->progress)->toBe(80);
});

it('stops propagation when container value does not change', function () {
    $root = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'progress' => 50,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'created_by' => $this->user->id,
    ]);

    $sub = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $root->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'progress' => 50,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'created_by' => $this->user->id,
    ]);

    Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $sub->id,
        'type' => TaskTypeEnum::TASK->value,
        'progress' => 50,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'created_by' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $sub->id,
        'type' => TaskTypeEnum::TASK->value,
        'progress' => 50,
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
        'created_by' => $this->user->id,
    ]);

    $this->service->recalculateAncestors($task);

    expect($sub->fresh()->progress)->toBe(50)
        ->and($root->fresh()->progress)->toBe(50);
});

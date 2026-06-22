<?php

use App\Enums\ProjectStatusEnum;
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
    $this->admin = User::factory()->create(['role_id' => 1]);
    $this->actingAs($this->admin, 'sanctum');

    $this->project = Project::factory()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->admin->id,
    ]);
});

// ─── Project State Machine ─────────────────────────────────────────────────

it('allows ACTIVE → ON_HOLD', function () {
    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::ON_HOLD->value,
    ])->assertStatus(200);

    expect($this->project->fresh()->project_status_id)->toBe(ProjectStatusEnum::ON_HOLD->value);
});

it('allows ACTIVE → COMPLETED', function () {
    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::COMPLETED->value,
    ])->assertStatus(200);

    expect($this->project->fresh()->project_status_id)->toBe(ProjectStatusEnum::COMPLETED->value);
});

it('allows ACTIVE → CANCELLED', function () {
    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::CANCELLED->value,
    ])->assertStatus(200);

    expect($this->project->fresh()->project_status_id)->toBe(ProjectStatusEnum::CANCELLED->value);
});

it('rejects ACTIVE → DELETED via update', function () {
    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::DELETED->value,
    ])->assertStatus(422);
});

it('rejects COMPLETED → CANCELLED', function () {
    $this->project->project_status_id = ProjectStatusEnum::COMPLETED->value;
    $this->project->save();

    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::CANCELLED->value,
    ])->assertStatus(422);
});

it('rejects same status transition', function () {
    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::ACTIVE->value,
    ])->assertStatus(422);
});

it('rejects update on DELETED project', function () {
    $this->project->project_status_id = ProjectStatusEnum::DELETED->value;
    $this->project->save();

    $this->patchJson("/api/projects/{$this->project->id}", [
        'name' => 'Updated',
    ])->assertStatus(422);
});

// ─── Task State Machine ────────────────────────────────────────────────────

it('allows task PENDING → IN_PROGRESS', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->admin->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::PENDING->value,
        'created_by' => $this->admin->id,
    ]);

    $this->patchJson("/api/tasks/{$task->id}", [
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
    ])->assertStatus(200);

    expect($task->fresh()->task_status_id)->toBe(TaskStatusEnum::IN_PROGRESS->value);
});

it('rejects task PENDING → COMPLETED directly', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->admin->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::PENDING->value,
        'created_by' => $this->admin->id,
    ]);

    $this->patchJson("/api/tasks/{$task->id}", [
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
    ])->assertStatus(422);
});

it('rejects task COMPLETED → CANCELLED', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->admin->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
        'created_by' => $this->admin->id,
    ]);

    $this->patchJson("/api/tasks/{$task->id}", [
        'task_status_id' => TaskStatusEnum::CANCELLED->value,
    ])->assertStatus(422);
});

it('rejects task update when DELETED', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->admin->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::DELETED->value,
        'created_by' => $this->admin->id,
    ]);

    $this->patchJson("/api/tasks/{$task->id}", [
        'title' => 'Updated',
    ])->assertStatus(422);
});

it('allows milestone PENDING → COMPLETED', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->admin->id,
    ]);

    $milestone = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::MILESTONE->value,
        'task_status_id' => TaskStatusEnum::PENDING->value,
        'start_date' => '2026-06-30',
        'created_by' => $this->admin->id,
    ]);

    $this->patchJson("/api/tasks/{$milestone->id}", [
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
    ])->assertStatus(200);

    expect($milestone->fresh()->task_status_id)->toBe(TaskStatusEnum::COMPLETED->value);
});

it('rejects milestone PENDING → IN_PROGRESS', function () {
    $container = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::CONTAINER->value,
        'created_by' => $this->admin->id,
    ]);

    $milestone = Task::factory()->create([
        'project_id' => $this->project->id,
        'parent_id' => $container->id,
        'type' => TaskTypeEnum::MILESTONE->value,
        'task_status_id' => TaskStatusEnum::PENDING->value,
        'start_date' => '2026-06-30',
        'created_by' => $this->admin->id,
    ]);

    $this->patchJson("/api/tasks/{$milestone->id}", [
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
    ])->assertStatus(422);
});

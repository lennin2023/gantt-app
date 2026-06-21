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

    $this->task = Task::factory()->create([
        'project_id' => $this->project->id,
        'type' => TaskTypeEnum::TASK->value,
        'task_status_id' => TaskStatusEnum::PENDING->value,
        'created_by' => $this->user->id,
    ]);
});

// ─── Transiciones válidas ──────────────────────────────────────────────────

it('allows PENDING → IN_PROGRESS', function () {
    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
    ])->assertOk();
});

it('allows PENDING → ON_HOLD', function () {
    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::ON_HOLD->value,
    ])->assertOk();
});

it('allows PENDING → CANCELLED', function () {
    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::CANCELLED->value,
    ])->assertOk();
});

it('allows IN_PROGRESS → PENDING', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::IN_PROGRESS->value]);

    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::PENDING->value,
    ])->assertOk();
});

it('allows IN_PROGRESS → COMPLETED', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::IN_PROGRESS->value]);

    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
    ])->assertOk();
});

it('allows IN_PROGRESS → ON_HOLD', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::IN_PROGRESS->value]);

    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::ON_HOLD->value,
    ])->assertOk();
});

it('allows COMPLETED → IN_PROGRESS', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::COMPLETED->value]);

    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
    ])->assertOk();
});

it('allows ON_HOLD → PENDING', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::ON_HOLD->value]);

    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::PENDING->value,
    ])->assertOk();
});

it('allows ON_HOLD → IN_PROGRESS', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::ON_HOLD->value]);

    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
    ])->assertOk();
});

it('allows CANCELLED → PENDING', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::CANCELLED->value]);

    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::PENDING->value,
    ])->assertOk();
});

// ─── Transiciones inválidas ────────────────────────────────────────────────

it('rejects PENDING → COMPLETED directly', function () {
    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
    ])->assertStatus(422);
});

it('rejects COMPLETED → CANCELLED directly', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::COMPLETED->value]);

    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::CANCELLED->value,
    ])->assertStatus(422);
});

it('rejects setting DELETED via update', function () {
    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::DELETED->value,
    ])->assertStatus(422);
});

it('rejects same status transition', function () {
    $this->patchJson("/api/tasks/{$this->task->id}", [
        'task_status_id' => TaskStatusEnum::PENDING->value,
    ])->assertStatus(422);
});

it('rejects update on DELETED task', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::DELETED->value]);

    $this->patchJson("/api/tasks/{$this->task->id}", [
        'title' => 'New title',
    ])->assertStatus(422);
});

// ─── Destroy ──────────────────────────────────────────────────────────────

it('allows destroy from PENDING', function () {
    $this->deleteJson("/api/tasks/{$this->task->id}")->assertOk();

    expect($this->task->fresh()->task_status_id)->toBe(TaskStatusEnum::DELETED->value);
});

it('allows destroy from IN_PROGRESS', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::IN_PROGRESS->value]);

    $this->deleteJson("/api/tasks/{$this->task->id}")->assertOk();
});

it('allows destroy from ON_HOLD', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::ON_HOLD->value]);

    $this->deleteJson("/api/tasks/{$this->task->id}")->assertOk();
});

it('allows destroy from CANCELLED', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::CANCELLED->value]);

    $this->deleteJson("/api/tasks/{$this->task->id}")->assertOk();
});

it('rejects destroy from COMPLETED', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::COMPLETED->value]);

    $this->deleteJson("/api/tasks/{$this->task->id}")->assertStatus(422);
});

it('rejects destroy from DELETED', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::DELETED->value]);

    $this->deleteJson("/api/tasks/{$this->task->id}")->assertStatus(422);
});

// ─── Restore ──────────────────────────────────────────────────────────────

it('allows restore from DELETED', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::DELETED->value]);

    $this->postJson("/api/tasks/{$this->task->id}/restore")->assertOk();

    expect($this->task->fresh()->task_status_id)->toBe(TaskStatusEnum::PENDING->value);
});

it('rejects restore from PENDING', function () {
    $this->postJson("/api/tasks/{$this->task->id}/restore")->assertStatus(422);
});

it('rejects restore from COMPLETED', function () {
    $this->task->update(['task_status_id' => TaskStatusEnum::COMPLETED->value]);

    $this->postJson("/api/tasks/{$this->task->id}/restore")->assertStatus(422);
});

// ─── Milestone ────────────────────────────────────────────────────────────

it('allows milestone PENDING → COMPLETED', function () {
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
        'created_by' => $this->user->id,
    ]);

    $this->patchJson("/api/tasks/{$milestone->id}", [
        'task_status_id' => TaskStatusEnum::COMPLETED->value,
    ])->assertOk();
});

it('rejects milestone PENDING → IN_PROGRESS', function () {
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
        'created_by' => $this->user->id,
    ]);

    $this->patchJson("/api/tasks/{$milestone->id}", [
        'task_status_id' => TaskStatusEnum::IN_PROGRESS->value,
    ])->assertStatus(422);
});

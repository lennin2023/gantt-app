<?php

use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TaskHelper;

uses(RefreshDatabase::class, TaskHelper::class);

beforeEach(function () {
    $this->superAdmin = User::where('email', 'superadmin@example.com')->first();
    $this->staff = User::where('email', 'staff@example.com')->first();
    $this->company = Company::first();

    $this->actingAs($this->superAdmin, 'sanctum');

    $this->project = Project::factory()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->superAdmin->id,
    ]);
});

// ─── Index ─────────────────────────────────────────────────────────────────

it('lists tasks for a project', function () {
    $this->createTask($this->project, $this->superAdmin);
    $this->createTask($this->project, $this->superAdmin);

    $this->getJson("/api/projects/{$this->project->id}/tasks")
        ->assertOk()
        ->assertJsonStructure(['data', 'meta']);
});

it('returns 404 for tasks of nonexistent project', function () {
    $this->getJson('/api/projects/999/tasks')->assertStatus(404);
});

// ─── Store ─────────────────────────────────────────────────────────────────

it('creates a container', function () {
    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::CONTAINER->value,
        'title' => 'Fase 1',
    ])->assertCreated()
        ->assertJsonPath('data.type.value', TaskTypeEnum::CONTAINER->value)
        ->assertJsonPath('data.path', '0001');
});

it('creates a task', function () {
    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::TASK->value,
        'title' => 'Mi tarea',
        'description' => 'Descripción',
        'start_date' => '2026-01-01',
        'end_date' => '2026-03-31',
        'progress' => 0,
    ])->assertCreated()
        ->assertJsonPath('data.type.value', TaskTypeEnum::TASK->value);
});

it('creates a milestone inside container', function () {
    $container = $this->createContainer($this->project, $this->superAdmin);

    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::MILESTONE->value,
        'title' => 'Hito',
        'parent_id' => $container->id,
        'start_date' => '2026-06-30',
    ])->assertCreated()
        ->assertJsonPath('data.type.value', TaskTypeEnum::MILESTONE->value);
});

it('auto-syncs milestone end_date to start_date on create', function () {
    $container = $this->createContainer($this->project, $this->superAdmin);

    $response = $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::MILESTONE->value,
        'title' => 'Hito',
        'parent_id' => $container->id,
        'start_date' => '2026-06-30',
    ]);

    $id = $response->json('data.id');
    $task = Task::find($id);

    expect($task->start_date->toDateString())->toBe('2026-06-30')
        ->and($task->end_date->toDateString())->toBe('2026-06-30');
});

it('rejects task without title', function () {
    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::TASK->value,
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

it('rejects container with start_date', function () {
    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::CONTAINER->value,
        'title' => 'Container',
        'start_date' => '2026-01-01',
    ])->assertStatus(422);
});

it('rejects milestone with description', function () {
    $container = $this->createContainer($this->project, $this->superAdmin);

    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::MILESTONE->value,
        'title' => 'Hito',
        'parent_id' => $container->id,
        'start_date' => '2026-06-30',
        'description' => 'no permitido',
    ])->assertStatus(422);
});

it('rejects milestone without start_date', function () {
    $container = $this->createContainer($this->project, $this->superAdmin);

    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::MILESTONE->value,
        'title' => 'Hito',
        'parent_id' => $container->id,
    ])->assertStatus(422);
});

it('rejects task with non-container parent', function () {
    $task = $this->createTask($this->project, $this->superAdmin);

    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::TASK->value,
        'title' => 'Child',
        'parent_id' => $task->id,
    ])->assertStatus(422);
});

it('rejects task with parent from another project', function () {
    $otherProject = Project::factory()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->superAdmin->id,
    ]);

    $container = $this->createContainer($otherProject, $this->superAdmin);

    $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'type' => TaskTypeEnum::TASK->value,
        'title' => 'Task',
        'parent_id' => $container->id,
    ])->assertStatus(422);
});

it('rejects self-dependency', function () {
    $task = $this->createTask($this->project, $this->superAdmin);

    $this->patchJson("/api/tasks/{$task->id}", [
        'dependency_ids' => [$task->id],
    ])->assertStatus(422);
});

it('rejects self-parent', function () {
    $container = $this->createContainer($this->project, $this->superAdmin);

    $this->patchJson("/api/tasks/{$container->id}", [
        'parent_id' => $container->id,
    ])->assertStatus(422);
});

// ─── Show ──────────────────────────────────────────────────────────────────

it('shows a task with relations', function () {
    $task = $this->createTask($this->project, $this->superAdmin);

    $this->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $task->id)
        ->assertJsonStructure(['data' => ['id', 'type', 'path', 'display_path', 'status']]);
});

it('returns 404 for nonexistent task', function () {
    $this->getJson('/api/tasks/999')->assertStatus(404);
});

// ─── Update ────────────────────────────────────────────────────────────────

it('updates task title', function () {
    $task = $this->createTask($this->project, $this->superAdmin);

    $this->patchJson("/api/tasks/{$task->id}", [
        'title' => 'Updated title',
    ])->assertOk()
        ->assertJsonPath('data.title', 'Updated title');
});

it('updates task progress', function () {
    $task = $this->createTask($this->project, $this->superAdmin);

    $this->patchJson("/api/tasks/{$task->id}", [
        'progress' => 75,
    ])->assertOk()
        ->assertJsonPath('data.progress', 75);
});

it('updates milestone start_date and syncs end_date', function () {
    $container = $this->createContainer($this->project, $this->superAdmin);
    $milestone = $this->createMilestone($this->project, $this->superAdmin, $container);

    $this->patchJson("/api/tasks/{$milestone->id}", [
        'start_date' => '2026-09-15',
    ])->assertOk();

    $fresh = $milestone->fresh();
    expect($fresh->start_date->toDateString())->toBe('2026-09-15')
        ->and($fresh->end_date->toDateString())->toBe('2026-09-15');
});

// ─── Path y display_path ───────────────────────────────────────────────────

it('assigns correct path on creation', function () {
    $container = $this->createContainer($this->project, $this->superAdmin);

    expect($container->fresh()->path)->toBe('0001');
});

it('assigns correct nested path', function () {
    $container = $this->createContainer($this->project, $this->superAdmin);
    $task = $this->createTask($this->project, $this->superAdmin, $container);

    expect($task->fresh()->path)->toBe('0001/0001');
});

it('returns correct display_path', function () {
    $container = $this->createContainer($this->project, $this->superAdmin);
    $sub = $this->createContainer($this->project, $this->superAdmin, $container);
    $task = $this->createTask($this->project, $this->superAdmin, $sub);

    $response = $this->getJson("/api/tasks/{$task->id}");

    expect($response->json('data.display_path'))->toBe('1.1.1');
});

it('sorts sibling paths correctly for more than 9 siblings', function () {
    $container = $this->createContainer($this->project, $this->superAdmin);

    for ($i = 0; $i < 13; $i++) {
        $this->createTask($this->project, $this->superAdmin, $container);
    }

    $tasks = Task::where('parent_id', $container->id)->orderBy('path')->get();

    expect($tasks->first()->path)->toBe('0001/0001')
        ->and($tasks->last()->path)->toBe('0001/0013');
});

<?php

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\TaskAssignment;
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

    $this->projectUser = ProjectUser::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->staff->id,
        'created_by' => $this->superAdmin->id,
    ]);

    $this->task = $this->createTask($this->project, $this->superAdmin);
});

// ─── Index ─────────────────────────────────────────────────────────────────

it('lists task assignments', function () {
    $this->getJson("/api/tasks/{$this->task->id}/assignments")
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('returns empty list when no assignments', function () {
    $response = $this->getJson("/api/tasks/{$this->task->id}/assignments");

    expect($response->json('data'))->toBeEmpty();
});

// ─── Store ─────────────────────────────────────────────────────────────────

it('assigns user to task without role', function () {
    $this->postJson("/api/tasks/{$this->task->id}/assignments", [
        'project_user_id' => $this->projectUser->id,
    ])->assertCreated()
        ->assertJsonPath('data.task_role', null);
});

it('assigns user to task with role', function () {
    $this->postJson("/api/tasks/{$this->task->id}/assignments", [
        'project_user_id' => $this->projectUser->id,
        'task_role_id' => 1,
    ])->assertCreated()
        ->assertJsonPath('data.task_role.id', 1);
});

it('rejects duplicate assignment', function () {
    TaskAssignment::factory()->create([
        'task_id' => $this->task->id,
        'project_user_id' => $this->projectUser->id,
    ]);

    $this->postJson("/api/tasks/{$this->task->id}/assignments", [
        'project_user_id' => $this->projectUser->id,
    ])->assertStatus(422);
});

it('rejects assignment with invalid project_user_id', function () {
    $this->postJson("/api/tasks/{$this->task->id}/assignments", [
        'project_user_id' => 999,
    ])->assertStatus(422);
});

it('rejects assignment with invalid task_role_id', function () {
    $this->postJson("/api/tasks/{$this->task->id}/assignments", [
        'project_user_id' => $this->projectUser->id,
        'task_role_id' => 999,
    ])->assertStatus(422);
});

// ─── Update ────────────────────────────────────────────────────────────────

it('updates task role', function () {
    $assignment = TaskAssignment::factory()->create([
        'task_id' => $this->task->id,
        'project_user_id' => $this->projectUser->id,
        'task_role_id' => 1,
    ]);

    $this->patchJson("/api/tasks/{$this->task->id}/assignments/{$assignment->id}", [
        'task_role_id' => 2,
    ])->assertOk()
        ->assertJsonPath('data.task_role.id', 2);
});

it('removes task role by setting null', function () {
    $assignment = TaskAssignment::factory()->create([
        'task_id' => $this->task->id,
        'project_user_id' => $this->projectUser->id,
        'task_role_id' => 1,
    ]);

    $this->patchJson("/api/tasks/{$this->task->id}/assignments/{$assignment->id}", [
        'task_role_id' => null,
    ])->assertOk()
        ->assertJsonPath('data.task_role', null);
});

it('returns 404 for nonexistent assignment', function () {
    $this->patchJson("/api/tasks/{$this->task->id}/assignments/999", [
        'task_role_id' => 1,
    ])->assertStatus(404);
});

// ─── Destroy ───────────────────────────────────────────────────────────────

it('removes assignment', function () {
    $assignment = TaskAssignment::factory()->create([
        'task_id' => $this->task->id,
        'project_user_id' => $this->projectUser->id,
    ]);

    $this->deleteJson("/api/tasks/{$this->task->id}/assignments/{$assignment->id}")
        ->assertOk();

    expect(TaskAssignment::find($assignment->id))->toBeNull();
});

it('returns 404 when removing nonexistent assignment', function () {
    $this->deleteJson("/api/tasks/{$this->task->id}/assignments/999")
        ->assertStatus(404);
});

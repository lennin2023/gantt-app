<?php

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::where('email', 'superadmin@example.com')->first();
    $this->staff = User::where('email', 'staff@example.com')->first();
    $this->admin = User::where('email', 'admin@example.com')->first();
    $this->company = Company::first();

    $this->actingAs($this->superAdmin, 'sanctum');

    $this->project = Project::factory()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->superAdmin->id,
    ]);
});

// ─── Index ─────────────────────────────────────────────────────────────────

it('lists project users', function () {
    ProjectUser::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->staff->id,
        'created_by' => $this->superAdmin->id,
    ]);

    $this->getJson("/api/projects/{$this->project->id}/users")
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('filters users by role', function () {
    ProjectUser::factory()->asManager()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->admin->id,
        'created_by' => $this->superAdmin->id,
    ]);

    ProjectUser::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->staff->id,
        'created_by' => $this->superAdmin->id,
    ]);

    $this->getJson("/api/projects/{$this->project->id}/users/role/1")
        ->assertOk();
});

// ─── Store ─────────────────────────────────────────────────────────────────

it('assigns a user to project', function () {
    $this->postJson("/api/projects/{$this->project->id}/users", [
        'user_id' => $this->staff->id,
        'project_role_id' => 2,
    ])->assertCreated();
});

it('rejects assigning same user twice', function () {
    ProjectUser::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->staff->id,
        'created_by' => $this->superAdmin->id,
    ]);

    $this->postJson("/api/projects/{$this->project->id}/users", [
        'user_id' => $this->staff->id,
        'project_role_id' => 2,
    ])->assertStatus(422);
});

it('rejects assigning user by non-admin', function () {
    $this->actingAs($this->staff, 'sanctum');

    $this->postJson("/api/projects/{$this->project->id}/users", [
        'user_id' => $this->admin->id,
        'project_role_id' => 2,
    ])->assertStatus(403);
});

// ─── Destroy ───────────────────────────────────────────────────────────────

it('removes a user from project', function () {
    ProjectUser::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->staff->id,
        'created_by' => $this->superAdmin->id,
    ]);

    $this->deleteJson("/api/projects/{$this->project->id}/users/{$this->staff->id}")
        ->assertOk();

    expect(
        ProjectUser::where('project_id', $this->project->id)
            ->where('user_id', $this->staff->id)
            ->exists()
    )->toBeFalse();
});

it('returns 404 when removing user not assigned to project', function () {
    $this->deleteJson("/api/projects/{$this->project->id}/users/{$this->staff->id}")
        ->assertStatus(404);
});

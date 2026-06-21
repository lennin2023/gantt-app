<?php

use App\Enums\ProjectStatusEnum;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['role_id' => 1]); // superadmin
    $this->actingAs($this->user, 'sanctum');

    $this->project = Project::factory()->create([
        'company_id' => $this->company->id,
        'project_status_id' => ProjectStatusEnum::ACTIVE->value,
        'created_by' => $this->user->id,
    ]);
});

// ─── Transiciones válidas ──────────────────────────────────────────────────

it('allows ACTIVE → ON_HOLD', function () {
    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::ON_HOLD->value,
    ])->assertOk();

    expect($this->project->fresh()->project_status_id)->toBe(ProjectStatusEnum::ON_HOLD->value);
});

it('allows ACTIVE → COMPLETED', function () {
    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::COMPLETED->value,
    ])->assertOk();
});

it('allows ACTIVE → CANCELLED', function () {
    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::CANCELLED->value,
    ])->assertOk();
});

it('allows ON_HOLD → ACTIVE', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::ON_HOLD->value]);

    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::ACTIVE->value,
    ])->assertOk();
});

it('allows ON_HOLD → CANCELLED', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::ON_HOLD->value]);

    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::CANCELLED->value,
    ])->assertOk();
});

it('allows COMPLETED → ACTIVE', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::COMPLETED->value]);

    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::ACTIVE->value,
    ])->assertOk();
});

it('allows CANCELLED → ACTIVE', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::CANCELLED->value]);

    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::ACTIVE->value,
    ])->assertOk();
});

// ─── Transiciones inválidas ────────────────────────────────────────────────

it('rejects same status transition', function () {
    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::ACTIVE->value,
    ])->assertStatus(422);
});

it('rejects ON_HOLD → COMPLETED directly', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::ON_HOLD->value]);

    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::COMPLETED->value,
    ])->assertStatus(422);
});

it('rejects COMPLETED → CANCELLED directly', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::COMPLETED->value]);

    $this->patchJson("/api/projects/{$this->project->id}", [
        'project_status_id' => ProjectStatusEnum::CANCELLED->value,
    ])->assertStatus(422);
});

it('rejects update on DELETED project', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::DELETED->value]);

    $this->patchJson("/api/projects/{$this->project->id}", [
        'name' => 'New name',
    ])->assertStatus(422);
});

// ─── Destroy ──────────────────────────────────────────────────────────────

it('allows destroy from ACTIVE', function () {
    $this->deleteJson("/api/projects/{$this->project->id}")
        ->assertOk();

    expect($this->project->fresh()->project_status_id)->toBe(ProjectStatusEnum::DELETED->value);
});

it('allows destroy from ON_HOLD', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::ON_HOLD->value]);

    $this->deleteJson("/api/projects/{$this->project->id}")->assertOk();
});

it('allows destroy from CANCELLED', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::CANCELLED->value]);

    $this->deleteJson("/api/projects/{$this->project->id}")->assertOk();
});

it('rejects destroy from COMPLETED', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::COMPLETED->value]);

    $this->deleteJson("/api/projects/{$this->project->id}")->assertStatus(422);
});

it('rejects destroy from DELETED', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::DELETED->value]);

    $this->deleteJson("/api/projects/{$this->project->id}")->assertStatus(422);
});

// ─── Restore ──────────────────────────────────────────────────────────────

it('allows restore from DELETED', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::DELETED->value]);

    $this->postJson("/api/projects/{$this->project->id}/restore")->assertOk();

    expect($this->project->fresh()->project_status_id)->toBe(ProjectStatusEnum::ACTIVE->value);
});

it('rejects restore from ACTIVE', function () {
    $this->postJson("/api/projects/{$this->project->id}/restore")->assertStatus(422);
});

it('rejects restore from ON_HOLD', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::ON_HOLD->value]);

    $this->postJson("/api/projects/{$this->project->id}/restore")->assertStatus(422);
});

it('rejects restore from COMPLETED', function () {
    $this->project->update(['project_status_id' => ProjectStatusEnum::COMPLETED->value]);

    $this->postJson("/api/projects/{$this->project->id}/restore")->assertStatus(422);
});

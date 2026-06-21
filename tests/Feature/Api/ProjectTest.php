<?php

use App\Enums\ProjectStatusEnum;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::where('email', 'superadmin@example.com')->first();
    $this->staff = User::where('email', 'staff@example.com')->first();
    $this->company = Company::first();

    $this->actingAs($this->superAdmin, 'sanctum');
});

// ─── Index ─────────────────────────────────────────────────────────────────

it('lists projects for authenticated user', function () {
    Project::factory(3)->create([
        'company_id' => $this->company->id,
        'created_by' => $this->superAdmin->id,
    ]);

    $this->getJson('/api/projects')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta']);
});

it('filters projects by status', function () {
    Project::factory()->create([
        'company_id' => $this->company->id,
        'project_status_id' => ProjectStatusEnum::ACTIVE->value,
        'created_by' => $this->superAdmin->id,
    ]);

    Project::factory()->create([
        'company_id' => $this->company->id,
        'project_status_id' => ProjectStatusEnum::ON_HOLD->value,
        'created_by' => $this->superAdmin->id,
    ]);

    $response = $this->getJson('/api/projects?status_id='.ProjectStatusEnum::ACTIVE->value);

    $response->assertOk();
    $data = $response->json('data');
    expect(collect($data)->every(fn ($p) => $p['project_status_id'] === ProjectStatusEnum::ACTIVE->value))->toBeTrue();
});

it('returns 401 without token', function () {
    $this->app['auth']->forgetGuards();
    $this->getJson('/api/projects')->assertStatus(401);
});

// ─── Store ─────────────────────────────────────────────────────────────────

it('creates a project', function () {
    $this->postJson('/api/projects', [
        'company_id' => $this->company->id,
        'name' => 'Test Project',
        'color' => '#3B82F6',
        'description' => 'Description',
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Test Project');
});

it('creates a project with initial history entry', function () {
    $response = $this->postJson('/api/projects', [
        'company_id' => $this->company->id,
        'name' => 'Test Project',
        'color' => '#3B82F6',
    ]);

    $projectId = $response->json('data.id');
    $history = ProjectHistory::where('project_id', $projectId)->get();

    expect($history)->toHaveCount(1)
        ->and($history->first()->project_status_id)->toBe(ProjectStatusEnum::ACTIVE->value);
});

it('rejects project without name', function () {
    $this->postJson('/api/projects', [
        'company_id' => $this->company->id,
        'color' => '#3B82F6',
    ])->assertStatus(422);
});

it('rejects project without company_id', function () {
    $this->postJson('/api/projects', [
        'name' => 'Test',
        'color' => '#3B82F6',
    ])->assertStatus(422);
});

it('rejects project creation by staff', function () {
    $this->actingAs($this->staff, 'sanctum');

    $this->postJson('/api/projects', [
        'company_id' => $this->company->id,
        'name' => 'Test',
        'color' => '#3B82F6',
    ])->assertStatus(403);
});

// ─── Show ──────────────────────────────────────────────────────────────────

it('shows a project', function () {
    $project = Project::factory()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->superAdmin->id,
    ]);

    $this->getJson("/api/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $project->id);
});

it('returns 404 for nonexistent project', function () {
    $this->getJson('/api/projects/999')->assertStatus(404);
});

// ─── Update ────────────────────────────────────────────────────────────────

it('updates a project', function () {
    $project = Project::factory()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->superAdmin->id,
    ]);

    $this->patchJson("/api/projects/{$project->id}", [
        'name' => 'Updated name',
    ])->assertOk()
        ->assertJsonPath('data.name', 'Updated name');
});

it('rejects partial update with invalid color', function () {
    $project = Project::factory()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->superAdmin->id,
    ]);

    $this->patchJson("/api/projects/{$project->id}", [
        'color' => 'not-a-color',
    ])->assertStatus(422);
});

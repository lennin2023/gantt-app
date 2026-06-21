<?php

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::where('email', 'superadmin@example.com')->first();
    $this->company = Company::first();

    $this->actingAs($this->superAdmin, 'sanctum');
});

it('returns dashboard stats', function () {
    $this->getJson('/api/dashboard/stats')
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'total_projects',
            'active_projects',
            'completed_projects',
            'total_tasks',
            'completed_tasks',
        ]]);
});

it('returns zero stats when no projects exist', function () {
    $response = $this->getJson('/api/dashboard/stats');

    expect($response->json('data.total_projects'))->toBe(0);
});

it('counts projects correctly', function () {
    Project::factory(3)->create([
        'company_id' => $this->company->id,
        'created_by' => $this->superAdmin->id,
    ]);

    $response = $this->getJson('/api/dashboard/stats');

    expect($response->json('data.total_projects'))->toBe(3);
});

it('returns 401 without token', function () {
    $this->getJson('/api/dashboard/stats')->assertStatus(401);
})->withoutMiddleware(EnsureFrontendRequestsAreStateful::class);

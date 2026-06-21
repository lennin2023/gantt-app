<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows login with valid credentials', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'superadmin@example.com',
        'password' => 'password',
        'device_name' => 'test',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user']]);
});

it('rejects login with wrong password', function () {
    $this->postJson('/api/auth/login', [
        'email' => 'superadmin@example.com',
        'password' => 'wrong',
        'device_name' => 'test',
    ])->assertStatus(422);
});

it('rejects login with unknown email', function () {
    $this->postJson('/api/auth/login', [
        'email' => 'notfound@example.com',
        'password' => 'password',
        'device_name' => 'test',
    ])->assertStatus(422);
});

it('returns authenticated user on me', function () {
    $user = User::where('email', 'superadmin@example.com')->first();
    $this->actingAs($user, 'sanctum');

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'superadmin@example.com');
});

it('rejects me without token', function () {
    $this->getJson('/api/auth/me')->assertStatus(401);
});

it('allows logout', function () {
    $user = User::where('email', 'superadmin@example.com')->first();
    $this->actingAs($user, 'sanctum');

    $this->postJson('/api/auth/logout')->assertOk();
});

it('allows logout all', function () {
    $user = User::where('email', 'superadmin@example.com')->first();
    $this->actingAs($user, 'sanctum');

    $this->postJson('/api/auth/logout-all')->assertOk();
});

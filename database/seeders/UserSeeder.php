<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            RoleEnum::SUPER_ADMIN->value => [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
            ],
            RoleEnum::SUPERVISOR->value => [
                'name' => 'Supervisor',
                'email' => 'supervisor@example.com',
            ],
            RoleEnum::GESTOR->value => [
                'name' => 'Gestor',
                'email' => 'gestor@example.com',
            ],
            RoleEnum::PROJECT_MANAGER->value => [
                'name' => 'Project Manager',
                'email' => 'manager@example.com',
            ],
            RoleEnum::TEAM_MEMBER->value => [
                'name' => 'Team Member',
                'email' => 'teammember@example.com',
            ],
            RoleEnum::VIEWER->value => [
                'name' => 'Viewer',
                'email' => 'viewer@example.com',
            ],
        ];

        foreach ($users as $roleId => $data) {
            User::factory()->create([
                'role_id' => $roleId,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
            ]);
        }
    }
}

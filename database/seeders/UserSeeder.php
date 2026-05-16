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
            RoleEnum::ADMIN->value => [
                'name' => 'Admin',
                'email' => 'admin@example.com',
            ],
            RoleEnum::COMPANY_OWNER->value => [
                'name' => 'Company Owner',
                'email' => 'owner@example.com',
            ],
            RoleEnum::PROJECT_MANAGER->value => [
                'name' => 'Project Manager',
                'email' => 'manager@example.com',
            ],
            RoleEnum::DEVELOPER->value => [
                'name' => 'Developer',
                'email' => 'developer@example.com',
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

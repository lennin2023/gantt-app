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
            RoleEnum::ADMIN->value => [
                'name' => 'Admin',
                'email' => 'admin@example.com',
            ],
            RoleEnum::STAFF->value => [
                'name' => 'Staff',
                'email' => 'staff@example.com',
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

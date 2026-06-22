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
            [
                'role_id' => RoleEnum::SUPER_ADMIN->value,
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
            ],
            [
                'role_id' => RoleEnum::SUPER_ADMIN->value,
                'name' => 'Super Admin Two',
                'email' => 'superadmin2@example.com',
            ],
            [
                'role_id' => RoleEnum::ADMIN->value,
                'name' => 'Admin',
                'email' => 'admin@example.com',
            ],
            [
                'role_id' => RoleEnum::STAFF->value,
                'name' => 'Staff',
                'email' => 'staff@example.com',
            ],
        ];

        $password = Hash::make(config('app.seeder_password', env('SEEDER_PASSWORD', 'password')));

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['password' => $password])
            );
        }
    }
}

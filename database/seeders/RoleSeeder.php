<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'Super Admin', 'slug' => 'super_admin'],
            ['id' => 2, 'name' => 'Admin',       'slug' => 'admin'],
            ['id' => 3, 'name' => 'Staff',       'slug' => 'staff'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['id' => $role['id']], $role);
        }
    }
}

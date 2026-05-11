<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin', 'level' => 5],
            ['name' => 'Company Owner', 'slug' => 'company_owner', 'level' => 4],
            ['name' => 'Project Manager', 'slug' => 'project_manager', 'level' => 3],
            ['name' => 'Developer', 'slug' => 'developer', 'level' => 2],
            ['name' => 'Viewer', 'slug' => 'viewer', 'level' => 1],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}

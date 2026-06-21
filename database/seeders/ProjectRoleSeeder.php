<?php

namespace Database\Seeders;

use App\Models\ProjectRole;
use Illuminate\Database\Seeder;

class ProjectRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'Project Manager', 'slug' => 'project_manager'],
            ['id' => 2, 'name' => 'Team Member',     'slug' => 'team_member'],
            ['id' => 3, 'name' => 'Viewer',          'slug' => 'viewer'],
        ];

        foreach ($roles as $role) {
            ProjectRole::firstOrCreate(['id' => $role['id']], $role);
        }
    }
}

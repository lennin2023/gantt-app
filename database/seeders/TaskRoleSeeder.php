<?php

namespace Database\Seeders;

use App\Models\TaskRole;
use Illuminate\Database\Seeder;

class TaskRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'Team Leader', 'slug' => 'team_leader'],
            ['id' => 2, 'name' => 'Developer',   'slug' => 'developer'],
            ['id' => 3, 'name' => 'Analyst',     'slug' => 'analyst'],
            ['id' => 4, 'name' => 'Designer',    'slug' => 'designer'],
            ['id' => 5, 'name' => 'Tester',      'slug' => 'tester'],
        ];

        foreach ($roles as $role) {
            TaskRole::firstOrCreate(['id' => $role['id']], $role);
        }
    }
}

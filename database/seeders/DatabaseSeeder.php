<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CompanySeeder::class,
            ProjectStatusSeeder::class,
            TaskStatusSeeder::class,
            ProjectRoleSeeder::class,
            TaskRoleSeeder::class,
            UserSeeder::class,
            AuditFieldsUpdaterSeeder::class,
        ]);
    }
}

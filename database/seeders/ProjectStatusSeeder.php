<?php

namespace Database\Seeders;

use App\Models\ProjectStatus;
use Illuminate\Database\Seeder;

class ProjectStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'active', 'color' => '#22c55e'],
            ['name' => 'completed', 'color' => '#3b82f6'],
            ['name' => 'archived', 'color' => '#6b7280'],
            ['name' => 'on_hold', 'color' => '#f59e0b'],
            ['name' => 'cancelled', 'color' => '#ef4444'],
        ];

        foreach ($statuses as $status) {
            ProjectStatus::create($status);
        }
    }
}

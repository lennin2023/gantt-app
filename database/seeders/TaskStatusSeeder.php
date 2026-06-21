<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['id' => 1, 'name' => 'Pendiente',   'slug' => 'pending',     'color' => '#6b7280'],
            ['id' => 2, 'name' => 'En Progreso', 'slug' => 'in_progress', 'color' => '#3b82f6'],
            ['id' => 3, 'name' => 'Completada',  'slug' => 'completed',   'color' => '#22c55e'],
            ['id' => 4, 'name' => 'En Pausa',    'slug' => 'on_hold',     'color' => '#f59e0b'],
            ['id' => 5, 'name' => 'Cancelada',   'slug' => 'cancelled',   'color' => '#ef4444'],
            ['id' => 6, 'name' => 'Eliminada',   'slug' => 'deleted',     'color' => '#1f2937'],
        ];

        foreach ($statuses as $status) {
            TaskStatus::firstOrCreate(['id' => $status['id']], $status);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\ProjectStatus;
use Illuminate\Database\Seeder;

class ProjectStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['id' => 1, 'name' => 'Activo',     'slug' => 'active',    'color' => '#22c55e'],
            ['id' => 2, 'name' => 'Completado', 'slug' => 'completed', 'color' => '#3b82f6'],
            ['id' => 3, 'name' => 'En Pausa',   'slug' => 'on_hold',   'color' => '#f59e0b'],
            ['id' => 4, 'name' => 'Cancelado',  'slug' => 'cancelled', 'color' => '#ef4444'],
            ['id' => 5, 'name' => 'Eliminado',  'slug' => 'deleted',   'color' => '#6b7280'],
        ];

        foreach ($statuses as $status) {
            ProjectStatus::firstOrCreate(['id' => $status['id']], $status);
        }
    }
}

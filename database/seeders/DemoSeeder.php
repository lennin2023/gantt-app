<?php

namespace Database\Seeders;

use App\Enums\ProjectStatusEnum;
use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::factory()->create([
            'name' => 'Empresa Demo',
        ]);

        $admin = User::where('role_id', 1)->first();
        $staff = User::where('role_id', 3)->first();

        if (! $admin || ! $staff) {
            $this->command->error('Se requieren usuarios con roles admin y staff. Ejecuta DatabaseSeeder primero.');

            return;
        }

        $projects = $this->createProjects($company, $admin, $staff);

        foreach ($projects as $project) {
            $this->createProjectStructure($project, $admin, $staff);
        }

        $this->command->info('Datos demo creados exitosamente.');
    }

    private function createProjects(Company $company, User $admin, User $staff): array
    {
        $projectData = [
            ['name' => 'Sistema de Gestión', 'color' => '#3B82F6', 'status' => ProjectStatusEnum::ACTIVE->value],
            ['name' => 'App Móvil v2', 'color' => '#10B981', 'status' => ProjectStatusEnum::ACTIVE->value],
            ['name' => 'Migración BD', 'color' => '#F59E0B', 'status' => ProjectStatusEnum::ON_HOLD->value],
            ['name' => 'Rediseño UI', 'color' => '#8B5CF6', 'status' => ProjectStatusEnum::COMPLETED->value],
            ['name' => 'Integración API', 'color' => '#EF4444', 'status' => ProjectStatusEnum::ACTIVE->value],
        ];

        $projects = [];

        foreach ($projectData as $data) {
            $project = Project::factory()->create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'color' => $data['color'],
                'project_status_id' => $data['status'],
                'created_by' => $admin->id,
                'start_date' => now()->subDays(rand(10, 60))->format('Y-m-d'),
                'end_date' => now()->addDays(rand(10, 90))->format('Y-m-d'),
            ]);

            ProjectUser::factory()->create([
                'project_id' => $project->id,
                'user_id' => $staff->id,
                'project_role_id' => 1, // Project Manager
                'created_by' => $admin->id,
            ]);

            $projects[] = $project;
        }

        return $projects;
    }

    private function createProjectStructure(Project $project, User $admin, User $staff): void
    {
        $containers = [
            'Planificación',
            'Desarrollo',
            'Testing',
            'Despliegue',
        ];

        $createdContainers = [];

        foreach ($containers as $containerName) {
            $container = Task::factory()->container()->create([
                'project_id' => $project->id,
                'title' => $containerName,
                'created_by' => $admin->id,
            ]);

            $createdContainers[] = $container;
        }

        $taskTemplates = [
            'Planificación' => [
                ['title' => 'Definir requerimientos', 'status' => TaskStatusEnum::COMPLETED->value, 'progress' => 100],
                ['title' => 'Crear wireframes', 'status' => TaskStatusEnum::COMPLETED->value, 'progress' => 100],
                ['title' => 'Aprobar diseño', 'status' => TaskStatusEnum::IN_PROGRESS->value, 'progress' => 50],
            ],
            'Desarrollo' => [
                ['title' => 'Configurar entorno', 'status' => TaskStatusEnum::COMPLETED->value, 'progress' => 100],
                ['title' => 'Implementar autenticación', 'status' => TaskStatusEnum::IN_PROGRESS->value, 'progress' => 75],
                ['title' => 'Desarrollar CRUD usuarios', 'status' => TaskStatusEnum::PENDING->value, 'progress' => 0],
                ['title' => 'Implementar API REST', 'status' => TaskStatusEnum::PENDING->value, 'progress' => 0],
            ],
            'Testing' => [
                ['title' => 'Tests unitarios', 'status' => TaskStatusEnum::PENDING->value, 'progress' => 0],
                ['title' => 'Tests de integración', 'status' => TaskStatusEnum::PENDING->value, 'progress' => 0],
                ['title' => 'QA manual', 'status' => TaskStatusEnum::PENDING->value, 'progress' => 0],
            ],
            'Despliegue' => [
                ['title' => 'Configurar CI/CD', 'status' => TaskStatusEnum::PENDING->value, 'progress' => 0],
                ['title' => 'Deploy a staging', 'status' => TaskStatusEnum::PENDING->value, 'progress' => 0],
                ['title' => 'Deploy a producción', 'status' => TaskStatusEnum::PENDING->value, 'progress' => 0],
            ],
        ];

        foreach ($createdContainers as $container) {
            $tasks = $taskTemplates[$container->title] ?? [];

            foreach ($tasks as $taskData) {
                $task = Task::factory()->create([
                    'project_id' => $project->id,
                    'parent_id' => $container->id,
                    'title' => $taskData['title'],
                    'task_status_id' => $taskData['status'],
                    'progress' => $taskData['progress'],
                    'type' => TaskTypeEnum::TASK->value,
                    'description' => fake()->paragraph(),
                    'start_date' => now()->subDays(rand(5, 30))->format('Y-m-d'),
                    'end_date' => now()->addDays(rand(5, 30))->format('Y-m-d'),
                    'created_by' => $admin->id,
                ]);
            }
        }

        foreach ($createdContainers as $container) {
            Task::factory()->milestone()->create([
                'project_id' => $project->id,
                'parent_id' => $container->id,
                'title' => 'Hito: '.$container->title.' completado',
                'start_date' => now()->addDays(rand(30, 90))->format('Y-m-d'),
                'created_by' => $admin->id,
            ]);
        }
    }
}

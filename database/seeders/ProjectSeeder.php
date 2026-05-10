<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@gantt.test'],
            ['name' => 'Admin User', 'password' => Hash::make('password')]
        );

        $company = Company::first();

        $project1 = Project::create([
            'created_by' => $user->id,
            'company_id' => $company->id,
            'project_status_id' => 1,
            'name' => 'Website Redesign',
            'description' => 'Complete redesign of the company website with new branding',
            'color' => '#3b82f6',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->addMonths(2)->endOfMonth(),
        ]);

        $task1 = Task::create([
            'project_id' => $project1->id,
            'name' => 'Design mockups',
            'description' => 'Create initial design mockups for all pages',
            'assignee' => 'Designer',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->addWeek(),
            'progress' => 100,
            'status' => 'completed',
            'order' => 1,
        ]);

        $task2 = Task::create([
            'project_id' => $project1->id,
            'name' => 'Develop frontend',
            'description' => 'Implement designs in HTML/CSS/JS',
            'assignee' => 'Developer',
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeeks(3),
            'progress' => 60,
            'status' => 'in_progress',
            'order' => 2,
        ]);

        $task3 = Task::create([
            'project_id' => $project1->id,
            'name' => 'Content writing',
            'description' => 'Write all website content',
            'assignee' => 'Writer',
            'start_date' => now()->addWeeks(2),
            'end_date' => now()->addWeeks(4),
            'progress' => 0,
            'status' => 'pending',
            'order' => 3,
        ]);

        $task4 = Task::create([
            'project_id' => $project1->id,
            'name' => 'QA testing',
            'description' => 'Test all pages and functionality',
            'assignee' => 'QA',
            'start_date' => now()->addWeeks(5),
            'end_date' => now()->addWeeks(6),
            'progress' => 0,
            'status' => 'pending',
            'order' => 4,
        ]);

        $task2->dependencies()->attach([$task1->id]);
        $task3->dependencies()->attach([$task1->id]);
        $task4->dependencies()->attach([$task2->id, $task3->id]);

        Milestone::create([
            'project_id' => $project1->id,
            'name' => 'Design Approval',
            'date' => now()->addWeek(),
            'reached' => true,
        ]);

        Milestone::create([
            'project_id' => $project1->id,
            'name' => 'Beta Release',
            'date' => now()->addWeeks(5),
            'reached' => false,
        ]);

        Milestone::create([
            'project_id' => $project1->id,
            'name' => 'Final Launch',
            'date' => now()->addMonths(2),
            'reached' => false,
        ]);

        $project2 = Project::create([
            'created_by' => $user->id,
            'company_id' => $company->id,
            'project_status_id' => 1,
            'name' => 'Mobile App MVP',
            'description' => 'Build a minimum viable product for the mobile app',
            'color' => '#22c55e',
            'start_date' => now()->startOfMonth()->addWeeks(2),
            'end_date' => now()->addMonths(3),
        ]);

        $task5 = Task::create([
            'project_id' => $project2->id,
            'name' => 'Setup project',
            'description' => 'Initialize React Native project with dependencies',
            'assignee' => 'Developer',
            'start_date' => now()->startOfMonth()->addWeeks(2),
            'end_date' => now()->startOfMonth()->addWeeks(2)->addDays(2),
            'progress' => 100,
            'status' => 'completed',
            'order' => 1,
        ]);

        $task6 = Task::create([
            'project_id' => $project2->id,
            'name' => 'User authentication',
            'description' => 'Implement login/signup flow',
            'assignee' => 'Developer',
            'start_date' => now()->startOfMonth()->addWeeks(2)->addDays(3),
            'end_date' => now()->startOfMonth()->addWeeks(3),
            'progress' => 30,
            'status' => 'in_progress',
            'order' => 2,
        ]);

        $task7 = Task::create([
            'project_id' => $project2->id,
            'name' => 'Dashboard UI',
            'description' => 'Create main dashboard screen',
            'assignee' => 'Developer',
            'start_date' => now()->startOfMonth()->addWeeks(3)->addDays(2),
            'end_date' => now()->startOfMonth()->addWeeks(4),
            'progress' => 0,
            'status' => 'pending',
            'order' => 3,
        ]);

        $task6->dependencies()->attach([$task5->id]);
        $task7->dependencies()->attach([$task6->id]);

        Milestone::create([
            'project_id' => $project2->id,
            'name' => 'Auth Module Complete',
            'date' => now()->startOfMonth()->addWeeks(3),
            'reached' => false,
        ]);

        Milestone::create([
            'project_id' => $project2->id,
            'name' => 'MVP Ready for Testing',
            'date' => now()->addMonths(3),
            'reached' => false,
        ]);
    }
}

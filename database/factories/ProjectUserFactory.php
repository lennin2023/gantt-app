<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectUserFactory extends Factory
{
    protected $model = ProjectUser::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'project_role_id' => 2, // Team Member
            'created_by' => 1,
        ];
    }

    public function asManager(): static
    {
        return $this->state(['project_role_id' => 1]); // Project Manager
    }

    public function asViewer(): static
    {
        return $this->state(['project_role_id' => 3]); // Viewer
    }
}

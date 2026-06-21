<?php

namespace Database\Factories;

use App\Models\ProjectUser;
use App\Models\Task;
use App\Models\TaskAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskAssignmentFactory extends Factory
{
    protected $model = TaskAssignment::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'project_user_id' => ProjectUser::factory(),
            'task_role_id' => null,
        ];
    }
}

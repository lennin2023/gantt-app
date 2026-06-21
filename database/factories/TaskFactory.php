<?php

namespace Database\Factories;

use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'parent_id' => null,
            'path' => '0000', // temporal, el observer lo corrige
            'task_status_id' => TaskStatusEnum::PENDING->value,
            'type' => TaskTypeEnum::TASK->value,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'start_date' => null,
            'end_date' => null,
            'progress' => 0,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function container(): static
    {
        return $this->state([
            'type' => TaskTypeEnum::CONTAINER->value,
            'description' => null,
            'progress' => 0,
            'start_date' => null,
            'end_date' => null,
        ]);
    }

    public function milestone(): static
    {
        return $this->state([
            'type' => TaskTypeEnum::MILESTONE->value,
            'description' => null,
            'progress' => 0,
        ]);
    }

    public function pending(): static
    {
        return $this->state(['task_status_id' => TaskStatusEnum::PENDING->value]);
    }

    public function inProgress(): static
    {
        return $this->state(['task_status_id' => TaskStatusEnum::IN_PROGRESS->value]);
    }

    public function completed(): static
    {
        return $this->state([
            'task_status_id' => TaskStatusEnum::COMPLETED->value,
            'progress' => 100,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['task_status_id' => TaskStatusEnum::CANCELLED->value]);
    }

    public function deleted(): static
    {
        return $this->state(['task_status_id' => TaskStatusEnum::DELETED->value]);
    }
}

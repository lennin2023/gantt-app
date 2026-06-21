<?php

namespace Database\Factories;

use App\Enums\ProjectStatusEnum;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'project_status_id' => ProjectStatusEnum::ACTIVE->value,
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'color' => fake()->hexColor(),
            'start_date' => null,
            'end_date' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['project_status_id' => ProjectStatusEnum::ACTIVE->value]);
    }

    public function onHold(): static
    {
        return $this->state(['project_status_id' => ProjectStatusEnum::ON_HOLD->value]);
    }

    public function completed(): static
    {
        return $this->state(['project_status_id' => ProjectStatusEnum::COMPLETED->value]);
    }

    public function cancelled(): static
    {
        return $this->state(['project_status_id' => ProjectStatusEnum::CANCELLED->value]);
    }

    public function deleted(): static
    {
        return $this->state(['project_status_id' => ProjectStatusEnum::DELETED->value]);
    }
}

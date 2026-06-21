<?php

namespace Tests\Helpers;

use App\Enums\TaskTypeEnum;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

trait TaskHelper
{
    protected function createContainer(Project $project, User $user, ?Task $parent = null): Task
    {
        $data = [
            'project_id' => $project->id,
            'type' => TaskTypeEnum::CONTAINER->value,
            'title' => 'Container',
            'created_by' => $user->id,
        ];

        if ($parent) {
            $data['parent_id'] = $parent->id;
        }

        return Task::factory()->container()->create($data);
    }

    protected function createTask(Project $project, User $user, ?Task $parent = null, array $attrs = []): Task
    {
        $data = array_merge([
            'project_id' => $project->id,
            'type' => TaskTypeEnum::TASK->value,
            'title' => 'Task',
            'created_by' => $user->id,
        ], $attrs);

        if ($parent) {
            $data['parent_id'] = $parent->id;
        }

        return Task::factory()->create($data);
    }

    protected function createMilestone(Project $project, User $user, Task $parent, array $attrs = []): Task
    {
        return Task::factory()->milestone()->create(array_merge([
            'project_id' => $project->id,
            'parent_id' => $parent->id,
            'title' => 'Milestone',
            'start_date' => '2026-06-30',
            'end_date' => '2026-06-30',
            'created_by' => $user->id,
        ], $attrs));
    }
}

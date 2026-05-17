<?php

namespace App\Http\Requests\Api;

use App\Enums\TaskStatusEnum;
use App\Repositories\Contracts\ProjectUserRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $isBulk = $this->routeIs('*.bulk-update');

        if ($isBulk) {
            return [
                'task_ids' => 'required|array|min:1',
                'task_ids.*' => 'integer|exists:tasks,id',
                'data' => 'required|array|min:1',
                'data.project_user_id' => [
                    'nullable',
                    'exists:project_users,id',
                    function ($attribute, $value, $fail) {
                        if ($value && $this->hasTaskIds()) {
                            $taskProjectId = $this->getProjectIdForProjectUser($value);
                            $this->validateBulkTasksBelongToProject($taskProjectId, $fail);
                        }
                    },
                ],
                'data.task_status_id' => ['nullable', new Enum(TaskStatusEnum::class)],
                'data.name' => 'sometimes|string|max:255',
                'data.description' => 'nullable|string',
                'data.start_date' => 'nullable|date',
                'data.end_date' => 'nullable|date|after_or_equal:data.start_date',
                'data.progress' => 'nullable|integer|min:0|max:100',
                'data.order' => 'nullable|integer|min:0',
            ];
        }

        $rules = [
            'project_user_id' => [
                'nullable',
                'exists:project_users,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->filled('dependency_ids')) {
                        $projectId = $this->getProjectIdForProjectUser($value);
                        $this->validateDependencyIdsBelongToProject($projectId, $fail);
                    }
                },
            ],
            'task_status_id' => ['nullable', new Enum(TaskStatusEnum::class)],
            'name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'progress' => 'nullable|integer|min:0|max:100',
            'order' => 'nullable|integer|min:0',
            'dependency_ids' => 'nullable|array',
            'dependency_ids.*' => 'integer|exists:tasks,id',
        ];

        return $rules;
    }

    protected function getProjectIdForProjectUser(int $projectUserId): ?int
    {
        $projectUserRepo = app(ProjectUserRepositoryInterface::class);
        $projectUser = $projectUserRepo->findById($projectUserId);

        return $projectUser?->project_id;
    }

    protected function validateDependencyIdsBelongToProject(?int $projectId, callable $fail): void
    {
        if (! $projectId) {
            return;
        }

        $dependencyIds = $this->input('dependency_ids', []);

        if (empty($dependencyIds)) {
            return;
        }

        $tasksProjectUserRepo = app(TaskRepositoryInterface::class);

        foreach ($dependencyIds as $depId) {
            $task = $tasksProjectUserRepo->findById($depId);
            if (! $task || ! $task->projectUser) {
                $fail('Dependency task does not exist');

                return;
            }
            if ($task->projectUser->project_id !== $projectId) {
                $fail('Dependency task does not belong to the same project');

                return;
            }
        }
    }

    protected function hasTaskIds(): bool
    {
        return ! empty($this->input('task_ids'));
    }

    protected function validateBulkTasksBelongToProject(?int $projectId, callable $fail): void
    {
        if (! $projectId) {
            return;
        }

        $taskIds = $this->input('task_ids', []);
        $taskRepo = app(TaskRepositoryInterface::class);

        foreach ($taskIds as $taskId) {
            $task = $taskRepo->findById($taskId);
            if (! $task || ! $task->projectUser) {
                $fail("Task {$taskId} does not exist");

                return;
            }
            if ($task->projectUser->project_id !== $projectId) {
                $fail("Task {$taskId} does not belong to the same project");

                return;
            }
        }
    }
}

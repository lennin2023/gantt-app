<?php

namespace App\Http\Requests\Api;

use App\Enums\TaskDependencyTypeEnum;
use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Models\Task;
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
                'data.task_status_id' => ['nullable', new Enum(TaskStatusEnum::class)],
                'data.title' => 'sometimes|string|max:255',
                'data.description' => 'nullable|string',
                'data.start_date' => 'nullable|date',
                'data.end_date' => 'nullable|date|after_or_equal:data.start_date',
                'data.progress' => 'nullable|integer|min:0|max:100',
                'data.order' => 'nullable|integer|min:0',
            ];
        }

        $type = $this->input('type')
            ?? $this->route('task')?->type?->value
            ?? TaskTypeEnum::TASK->value;

        return [
            'type' => [
                $isUpdate ? 'sometimes' : 'required',
                new Enum(TaskTypeEnum::class),
            ],
            'parent_id' => [
                'nullable',
                'exists:tasks,id',
                function ($attribute, $value, $fail) {
                    $task = $this->route('task');
                    if ($task && (int) $value === $task->id) {
                        $fail(__('validation.task.self_parent'));
                    }

                    $projectId = $this->route('project')?->id
                        ?? $this->route('task')?->project_id;

                    if ($projectId && Task::where('id', $value)
                        ->where('project_id', '!=', $projectId)
                        ->exists()) {
                        $fail(__('validation.task.parent_different_project'));
                    }
                },
            ],
            'task_status_id' => $type === TaskTypeEnum::CONTAINER->value
                ? 'prohibited'
                : ['nullable', new Enum(TaskStatusEnum::class)],

            'title' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'description' => $type === TaskTypeEnum::MILESTONE->value
                ? 'prohibited'
                : 'nullable|string',

            'start_date' => $type === TaskTypeEnum::MILESTONE->value
                ? ($isUpdate ? 'sometimes|date' : 'required|date')
                : 'nullable|date',

            'end_date' => $type === TaskTypeEnum::MILESTONE->value
                ? 'prohibited'  // se auto-asigna igual a start_date
                : 'nullable|date|after_or_equal:start_date',

            'progress' => $type === TaskTypeEnum::CONTAINER->value
                ? 'prohibited'
                : 'nullable|integer|min:0|max:100',

            'order' => 'nullable|integer|min:0',
            'dependency_ids' => 'nullable|array',
            'dependency_ids.*' => [
                'integer',
                'exists:tasks,id',
                function ($attribute, $value, $fail) {
                    $projectId = $this->route('project')?->id
                        ?? $this->route('task')?->project_id;

                    if ($projectId && Task::where('id', $value)
                        ->where('project_id', '!=', $projectId)
                        ->exists()) {
                        $fail(__('validation.task.dependency_different_project'));
                    }

                    $task = $this->route('task');
                    if ($task && (int) $value === $task->id) {
                        $fail(__('validation.task.self_dependency'));
                    }
                },
            ],
            'dependency_type' => ['nullable', new Enum(TaskDependencyTypeEnum::class)],
        ];
    }
}

<?php

namespace App\Http\Requests\Api;

use App\Enums\TaskDependencyTypeEnum;
use App\Enums\TaskStatusEnum;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TaskRequest extends FormRequest
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepo,
    ) {
        parent::__construct();
    }

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

        return [
            'parent_id' => 'nullable|exists:tasks,id',
            'task_status_id' => ['nullable', new Enum(TaskStatusEnum::class)],
            'title' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'progress' => 'nullable|integer|min:0|max:100',
            'order' => 'nullable|integer|min:0',
            'dependency_ids' => 'nullable|array',
            'dependency_ids.*' => [
                'integer',
                'exists:tasks,id',
                function ($attribute, $value, $fail) {
                    $task = $this->route('task');
                    if ($task && (int) $value === $task->id) {
                        $fail('Una tarea no puede depender de sí misma.');
                    }
                },
            ],
            'dependency_type' => ['nullable', new Enum(TaskDependencyTypeEnum::class)],
        ];
    }
}

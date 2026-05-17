<?php

namespace App\Http\Requests\Api;

use App\Enums\TaskStatusEnum;
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
                'data.project_user_id' => 'nullable|exists:project_users,id',
                'data.task_status_id' => ['nullable', new Enum(TaskStatusEnum::class)],
                'data.name' => 'sometimes|string|max:255',
                'data.description' => 'nullable|string',
                'data.start_date' => 'nullable|date',
                'data.end_date' => 'nullable|date|after_or_equal:data.start_date',
                'data.progress' => 'nullable|integer|min:0|max:100',
                'data.order' => 'nullable|integer|min:0',
            ];
        }

        return [
            'project_user_id' => 'nullable|exists:project_users,id',
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
    }
}

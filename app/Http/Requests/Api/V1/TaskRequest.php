<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');

        return [
            'project_id' => $isUpdate ? 'sometimes|integer|exists:projects,id' : 'required|integer|exists:projects,id',
            'name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'progress' => 'nullable|integer|min:0|max:100',
            'status' => 'nullable|string|in:pending,in_progress,completed,delayed',
            'order' => 'nullable|integer|min:0',
            'dependency_ids' => 'nullable|array',
            'dependency_ids.*' => 'integer|exists:tasks,id',
        ];
    }
}

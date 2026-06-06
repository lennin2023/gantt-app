<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TaskAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH');

        return [
            'project_user_id' => $isUpdate ? 'sometimes|exists:project_users,id' : 'required|exists:project_users,id',
            'task_role_id' => 'nullable|exists:task_roles,id',
        ];
    }
}

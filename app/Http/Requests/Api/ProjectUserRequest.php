<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ProjectUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');

        return [
            'user_id' => $isUpdate ? 'sometimes|exists:users,id' : 'required|exists:users,id',
            'project_role_id' => $isUpdate ? 'sometimes|exists:project_roles,id' : 'required|exists:project_roles,id',
        ];
    }
}

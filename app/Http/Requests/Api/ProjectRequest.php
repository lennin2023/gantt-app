<?php

namespace App\Http\Requests\Api;

use App\Enums\ProjectStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProjectRequest extends FormRequest
{
    // Authorization is handled via Gates in the controller
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');

        return [
            'company_id' => $isUpdate ? 'sometimes|integer|exists:companies,id' : 'required|integer|exists:companies,id',
            'project_status_id' => ['nullable', new Enum(ProjectStatusEnum::class)],
            'name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => $isUpdate
                ? ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/']
                : ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
}

<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');

        return [
            'company_id' => $isUpdate ? 'sometimes|integer|exists:companies,id' : 'required|integer|exists:companies,id',
            'name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
}

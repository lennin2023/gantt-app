<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class MilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');

        return [
            'name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'date' => $isUpdate ? 'sometimes|date' : 'required|date',
            'reached' => 'nullable|boolean',
        ];
    }
}

<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'is_active' => 'nullable|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $project = $this->route('project');
            $date = $this->input('date');

            if ($project && $date && ! $this->isMethod('PUT') && ! $this->isMethod('PATCH')) {
                if ($project->start_date && $date < $project->start_date) {
                    $validator->errors()->add('date', __('validation.milestone.date_before_project_start'));
                }
                if ($project->end_date && $date > $project->end_date) {
                    $validator->errors()->add('date', __('validation.milestone.date_after_project_end'));
                }
            }
        });
    }
}

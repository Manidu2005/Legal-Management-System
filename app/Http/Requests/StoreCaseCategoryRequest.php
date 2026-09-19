<?php

namespace App\Http\Requests;

use App\Models\CaseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'level' => ['required', 'integer', Rule::in(CaseCategory::LEVELS)],
            'parent_id' => [
                'nullable',
                'required_unless:level,' . CaseCategory::LEVEL_MAIN_TYPE,
                Rule::exists('case_categories', 'id')->where(function ($query) {
                    $query->where('level', ((int) $this->input('level')) - 1);
                }),
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_id' => 'parent category',
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.required_unless' => 'A parent category is required for a group or specific type.',
            'parent_id.exists' => 'The selected parent category is not valid for this level.',
        ];
    }
}

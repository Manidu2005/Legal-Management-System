<?php

namespace App\Http\Requests;

use App\Models\CaseCategory;
use App\Models\LegalCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLegalCaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'assigned_attorney_id' => 'required|exists:users,id',
            'name' => 'nullable|string|max:255',
            'case_category_id' => [
                'required',
                'integer',
                Rule::exists('case_categories', 'id')->where('is_active', true)->where('level', CaseCategory::LEVEL_SPECIFIC_TYPE),
            ],
            'court_id' => ['nullable', 'integer', Rule::exists('courts', 'id')->where('is_active', true)],
            'applicable_law' => ['nullable', Rule::in(array_keys(LegalCase::APPLICABLE_LAWS))],
            'case_type_other' => ['nullable', 'string', 'max:255'],
            'status' => 'required|in:pending,active,trial_scheduled,judgment_delivered,case_closed',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'client',
            'assigned_attorney_id' => 'assigned attorney',
            'case_category_id' => 'case type',
            'court_id' => 'court / forum',
            'applicable_law' => 'applicable law',
            'case_type_other' => 'case type detail',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'case_category_id.exists' => 'Please select a valid, specific case type from the list.',
        ];
    }
}

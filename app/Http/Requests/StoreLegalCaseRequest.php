<?php

namespace App\Http\Requests;

use App\Models\LegalCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLegalCaseRequest extends FormRequest
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
            'case_type' => ['nullable', 'string', Rule::in(LegalCase::CASE_TYPES)],
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
            'case_type' => 'case type',
        ];
    }
}

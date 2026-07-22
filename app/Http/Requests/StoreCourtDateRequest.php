<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourtDateRequest extends FormRequest
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
            'case_id' => ['required', 'exists:legal_cases,id'],
            'date' => ['required', 'date', 'after:now'],
            'type' => ['required', 'in:calling_date,trial_date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Please select a court date type (Calling Date or Trial Date).',
            'type.in' => 'Please select a court date type (Calling Date or Trial Date).',
            'date.after' => 'The court date must be in the future.',
            'case_id.required' => 'Please select a case.',
            'case_id.exists' => 'The selected case does not exist.',
        ];
    }
}

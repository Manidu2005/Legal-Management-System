<?php

namespace App\Http\Requests;

use App\Models\Judgment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJudgmentRequest extends FormRequest
{
    /**
     * Any authenticated user may upload to the shared judgment library.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::in(Judgment::CATEGORIES)],
            'court' => ['nullable', 'string', 'max:255'],
            'decided_date' => ['nullable', 'date'],
            'pdf' => [
                'required',
                'file',
                'mimes:pdf',
                'max:'.Judgment::MAX_FILE_SIZE_KB,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pdf.required' => 'Please upload a PDF judgment.',
            'pdf.max' => 'The PDF exceeds the 25MB limit.',
            'pdf.mimes' => 'Only PDF files are accepted.',
        ];
    }
}

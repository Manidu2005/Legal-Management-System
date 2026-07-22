<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'case_id' => ['required', 'exists:legal_cases,id'],
            'document' => [
                'required',
                'file',
                'mimes:pdf,jpg,png',
                'max:' . Document::MAX_FILE_SIZE_KB,
            ],
            'category' => [
                'required',
                Rule::in(Document::CATEGORIES),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'document.max' => 'File size exceeds 25MB limit.',
            'document.mimes' => 'File type not supported. Allowed: PDF, JPG, PNG.',
        ];
    }
}

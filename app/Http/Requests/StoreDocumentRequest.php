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
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => [
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
            'documents.required' => 'Please upload at least one document.',
            'documents.*.max' => 'One or more files exceed the 25MB limit.',
            'documents.*.mimes' => 'One or more files have an unsupported type. Allowed: PDF, JPG, PNG.',
        ];
    }
}

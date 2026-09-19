<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RenameDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only the uploader or a partner (manage-users) may rename a document —
     * mirrors the research-note delete rule.
     */
    public function authorize(): bool
    {
        $document = $this->route('document');

        if (! $document) {
            return false;
        }

        return $document->uploaded_by === $this->user()->id || Gate::allows('manage-users');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}

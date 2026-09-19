<?php

namespace App\Http\Requests;

use App\Models\LegalCase;
use App\Models\ResearchNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreResearchNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Anyone who can view the case can add a research note to it,
     * reusing the same CaseAccessPolicy::view check that gates documents.
     */
    public function authorize(): bool
    {
        $case = LegalCase::find($this->input('case_id'));

        if (! $case) {
            return false;
        }

        return Gate::allows('view', $case);
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
            'category' => ['required', 'in:' . implode(',', ResearchNote::CATEGORIES)],
            'citation' => ['required', 'string', 'max:500'],
            'court_or_source' => ['nullable', 'string', 'max:255'],
            'note' => ['required', 'string', 'max:5000'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}

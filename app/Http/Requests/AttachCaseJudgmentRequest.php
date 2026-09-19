<?php

namespace App\Http\Requests;

use App\Models\LegalCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AttachCaseJudgmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof LegalCase && Gate::allows('view', $case);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var LegalCase $case */
        $case = $this->route('case');

        return [
            'judgment_id' => [
                'required',
                'exists:judgments,id',
                Rule::unique('case_judgment', 'judgment_id')
                    ->where(fn ($query) => $query->where('legal_case_id', $case->id)),
            ],
            'relevance_note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judgment_id.unique' => 'That judgment is already attached to this case.',
        ];
    }
}

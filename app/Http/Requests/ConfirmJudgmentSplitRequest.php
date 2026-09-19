<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmJudgmentSplitRequest extends FormRequest
{
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
            'cases' => ['required', 'array', 'min:1'],
            'cases.*.citation' => ['nullable', 'string', 'max:500'],
            'cases.*.court' => ['nullable', 'string', 'max:255'],
            'cases.*.decided_date' => ['nullable', 'date'],
            'cases.*.start_page' => ['required', 'integer', 'min:1'],
            'cases.*.end_page' => ['required', 'integer', 'min:1'],
            'cases.*.cited_acts' => ['nullable', 'string', 'max:5000'],
            'cases.*.include' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('cases', []) as $index => $case) {
                if (! is_array($case)) {
                    continue;
                }
                $start = (int) ($case['start_page'] ?? 0);
                $end = (int) ($case['end_page'] ?? 0);
                if ($end < $start) {
                    $validator->errors()->add(
                        "cases.{$index}.end_page",
                        'The end page must be greater than or equal to the start page.'
                    );
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $cases = $this->input('cases', []);

        if (! is_array($cases)) {
            return;
        }

        foreach ($cases as $i => $case) {
            if (! is_array($case)) {
                continue;
            }
            // Checkbox: missing means not included
            $cases[$i]['include'] = filter_var($case['include'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $this->merge(['cases' => $cases]);
    }
}

<?php

namespace App\Http\Requests;

use App\Models\LegalCase;
use App\Models\LedgerEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreLedgerEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Associates may only create ledger entries on their own assigned cases.
     * Partners may create entries on any case.
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
            'type' => ['required', 'in:' . implode(',', LedgerEntry::TYPES)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $description = $this->input('description', '');
            $type = $this->input('type', '');

            if (stripos($description, 'retainer') !== false && $type === 'operational') {
                $validator->errors()->add(
                    'type',
                    'Retainer funds must be recorded in the Client Trust Ledger.'
                );
            }
        });
    }
}

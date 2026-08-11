<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
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
        $clientId = $this->route('client')->id;

        return [
            'name'        => 'required|string|max:255',
            'nic'         => ['required', 'string', 'regex:/^(\d{12}|\d{9}[Vv])$/', 'unique:clients,nic,' . $clientId],
            'phone'       => ['nullable', 'regex:/^\d{10}$/'],
            'email'       => 'nullable|email|max:255',
            'intake_date' => 'required|date',
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
            'nic.required' => 'NIC is required.',
            'nic.unique'   => 'A client with this NIC already exists.',
            'nic.regex'    => 'NIC must be 12 digits (new format) or 9 digits followed by V (old format, e.g. 123456789V).',
            'phone.regex'  => 'Phone number must be exactly 10 digits.',
            'email.email'  => 'Please enter a valid email address containing @.',
        ];
    }
}

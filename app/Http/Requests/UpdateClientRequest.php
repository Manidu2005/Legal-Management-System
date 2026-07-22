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
            'name' => 'required|string|max:255',
            'nic' => 'required|string|max:20|unique:clients,nic,' . $clientId,
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
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
            'nic.unique' => 'A client with this NIC already exists.',
        ];
    }
}

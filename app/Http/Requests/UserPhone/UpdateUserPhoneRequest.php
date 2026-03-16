<?php

namespace App\Http\Requests\UserPhone;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateUserPhoneRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => is_string($this->phone) ? trim($this->phone) : $this->phone,
            'label' => is_string($this->label) ? (trim($this->label) ?: null) : $this->label,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'is_whatsapp' => ['nullable', 'boolean'],
            'is_primary' => ['nullable', 'boolean'],
            'label' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            $phoneId = $this->route('id');

            if ($this->filled('phone')) {
                $duplicate = $user->phones()
                    ->where('phone', $this->input('phone'))
                    ->where('id', '!=', $phoneId)
                    ->exists();

                if ($duplicate) {
                    $validator->errors()->add('phone', 'This phone number is already registered.');
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'The phone number is required.',
            'phone.max' => 'The phone number must not exceed 20 characters.',
            'label.max' => 'The label must not exceed 50 characters.',
        ];
    }
}

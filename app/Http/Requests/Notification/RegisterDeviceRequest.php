<?php

namespace App\Http\Requests\Notification;

use App\Enums\DevicePlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDeviceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'device_token' => ['required', 'string', 'max:500'],
            'platform' => ['required', 'string', Rule::in(array_column(DevicePlatform::cases(), 'value'))],
            'device_name' => ['nullable', 'string', 'max:255'],
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
            'platform.in' => 'The platform must be IOS or ANDROID.',
        ];
    }
}

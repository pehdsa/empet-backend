<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notify_lost_nearby' => ['sometimes', 'boolean'],
            'notify_matches' => ['sometimes', 'boolean'],
            'notify_sightings' => ['sometimes', 'boolean'],
            'nearby_radius_km' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude', 'prohibited_if:clear_location,true'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude', 'prohibited_if:clear_location,true'],
            'clear_location' => ['sometimes', 'boolean'],
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
            'latitude.prohibited_if' => 'Cannot set location and clear it at the same time.',
            'longitude.prohibited_if' => 'Cannot set location and clear it at the same time.',
        ];
    }
}

<?php

namespace App\Http\Requests\PetReport;

use App\Enums\PetReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PetReportIndexRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pet_id' => ['nullable', 'integer', 'exists:pets,id'],
            'status' => ['nullable', 'string', Rule::in(array_column(PetReportStatus::cases(), 'value'))],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'radius_km' => ['nullable', 'numeric', 'min:1', 'max:100'],
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
            'status.in' => 'The status must be one of: LOST, FOUND, CANCELLED.',
            'latitude.required_with' => 'Latitude is required when longitude is provided.',
            'longitude.required_with' => 'Longitude is required when latitude is provided.',
        ];
    }
}

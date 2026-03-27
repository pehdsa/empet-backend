<?php

namespace App\Http\Requests\PetReport;

use App\Enums\PetSize;
use App\Enums\PetSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PetReportLostRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'species' => ['nullable', 'string', Rule::in(array_column(PetSpecies::cases(), 'value'))],
            'size' => ['nullable', 'string', Rule::in(array_column(PetSize::cases(), 'value'))],
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
            'latitude.required' => 'Latitude is required.',
            'longitude.required' => 'Longitude is required.',
            'species.in' => 'The species must be one of: DOG, CAT.',
            'size.in' => 'The size must be one of: SMALL, MEDIUM, LARGE.',
        ];
    }
}

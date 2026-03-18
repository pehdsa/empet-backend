<?php

namespace App\Http\Requests\PetReport;

use App\Enums\PetReportStatus;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
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
            'species' => ['nullable', 'string', Rule::in(array_column(PetSpecies::cases(), 'value'))],
            'size' => ['nullable', 'string', Rule::in(array_column(PetSize::cases(), 'value'))],
            'paginate' => ['nullable', 'string', 'in:true,false'],
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
            'species.in' => 'The species must be one of: DOG, CAT.',
            'size.in' => 'The size must be one of: SMALL, MEDIUM, LARGE.',
            'paginate.in' => 'The paginate field must be one of: true, false.',
        ];
    }
}

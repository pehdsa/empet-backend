<?php

namespace App\Http\Requests\PetSighting;

use Illuminate\Foundation\Http\FormRequest;

class ListPetSightingRequest extends FormRequest
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
            'radius_km' => ['nullable', 'numeric', 'gt:0', 'max:50'],
            'species' => ['nullable', 'string', 'in:DOG,CAT'],
            'size' => ['nullable', 'string', 'in:SMALL,MEDIUM,LARGE'],
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
            'radius_km.gt' => 'The radius must be greater than 0.',
            'radius_km.max' => 'The radius cannot exceed 50 km.',
        ];
    }
}

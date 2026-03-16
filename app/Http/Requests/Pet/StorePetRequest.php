<?php

namespace App\Http\Requests\Pet;

use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'species' => ['required', 'string', Rule::in(array_column(PetSpecies::cases(), 'value'))],
            'size' => ['required', 'string', Rule::in(array_column(PetSize::cases(), 'value'))],
            'sex' => ['required', 'string', Rule::in(array_column(PetSex::cases(), 'value'))],
            'breed' => ['nullable', 'string', 'max:255'],
            'secondary_breed' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:100'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'characteristic_ids' => ['nullable', 'array'],
            'characteristic_ids.*' => [
                'integer',
                Rule::exists('characteristics', 'id')->where('is_active', true),
            ],
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
            'species.in' => 'The species must be one of: DOG, CAT.',
            'size.in' => 'The size must be one of: SMALL, MEDIUM, LARGE.',
            'sex.in' => 'The sex must be one of: MALE, FEMALE, UNKNOWN.',
            'photos.max' => 'A pet can have a maximum of 5 photos.',
            'characteristic_ids.*.exists' => 'One or more selected characteristics are invalid or inactive.',
        ];
    }
}

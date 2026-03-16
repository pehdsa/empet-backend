<?php

namespace App\Http\Requests\Breed;

use App\Enums\PetSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BreedIndexRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'species' => ['nullable', 'string', Rule::in(array_column(PetSpecies::cases(), 'value'))],
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
        ];
    }
}

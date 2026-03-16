<?php

namespace App\Http\Requests\Pet;

use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
use App\Models\Breed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'breed_id' => ['nullable', 'integer', Rule::exists('breeds', 'id')->where('is_active', true)],
            'secondary_breed_id' => ['nullable', 'integer', Rule::exists('breeds', 'id')->where('is_active', true), 'different:breed_id'],
            'breed_description' => ['nullable', 'string', 'max:255'],
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
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $species = $this->input('species');

            if ($this->filled('breed_id') && $species) {
                $breed = Breed::find($this->input('breed_id'));
                if ($breed && $breed->species->value !== $species) {
                    $validator->errors()->add('breed_id', 'The selected breed does not match the pet species.');
                }
            }

            if ($this->filled('secondary_breed_id') && $species) {
                $breed = Breed::find($this->input('secondary_breed_id'));
                if ($breed && $breed->species->value !== $species) {
                    $validator->errors()->add('secondary_breed_id', 'The selected secondary breed does not match the pet species.');
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
            'species.in' => 'The species must be one of: DOG, CAT.',
            'size.in' => 'The size must be one of: SMALL, MEDIUM, LARGE.',
            'sex.in' => 'The sex must be one of: MALE, FEMALE, UNKNOWN.',
            'breed_id.exists' => 'The selected breed is invalid or inactive.',
            'secondary_breed_id.exists' => 'The selected secondary breed is invalid or inactive.',
            'secondary_breed_id.different' => 'The secondary breed must be different from the primary breed.',
            'photos.max' => 'A pet can have a maximum of 5 photos.',
            'characteristic_ids.*.exists' => 'One or more selected characteristics are invalid or inactive.',
        ];
    }
}

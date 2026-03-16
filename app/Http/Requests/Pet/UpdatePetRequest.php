<?php

namespace App\Http\Requests\Pet;

use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePetRequest extends FormRequest
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
            'new_photos' => ['nullable', 'array'],
            'new_photos.*' => ['image', 'max:2048'],
            'delete_photo_ids' => ['nullable', 'array'],
            'delete_photo_ids.*' => [
                'integer',
                Rule::exists('pet_photos', 'id')->where('pet_id', $this->route('pet')?->id),
            ],
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
            $pet = $this->route('pet');
            if (! $pet) {
                return;
            }

            $currentCount = $pet->photos()->count();
            $deleteCount = count($this->input('delete_photo_ids', []));
            $newCount = count($this->file('new_photos', []));

            if (($currentCount - $deleteCount + $newCount) > 5) {
                $validator->errors()->add(
                    'new_photos',
                    'A pet can have a maximum of 5 photos. Current: '.$currentCount.', removing: '.$deleteCount.', adding: '.$newCount.'.',
                );
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
            'delete_photo_ids.*.exists' => 'One or more photo IDs are invalid or do not belong to this pet.',
            'characteristic_ids.*.exists' => 'One or more selected characteristics are invalid or inactive.',
        ];
    }
}

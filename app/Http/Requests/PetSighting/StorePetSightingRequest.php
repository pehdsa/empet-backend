<?php

namespace App\Http\Requests\PetSighting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePetSightingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'sighted_at' => ['required', 'date', 'before_or_equal:now'],
            'species' => ['required', 'string', 'in:DOG,CAT'],
            'size' => ['nullable', 'string', 'in:SMALL,MEDIUM,LARGE'],
            'sex' => ['nullable', 'string', 'in:MALE,FEMALE,UNKNOWN'],
            'color' => ['nullable', 'string', 'max:100'],
            'breed_id' => [
                'nullable',
                'integer',
                Rule::exists('breeds', 'id')->where('species', $this->species),
            ],
            'address_hint' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'share_phone' => ['sometimes', 'boolean'],
            'characteristic_ids' => ['nullable', 'array'],
            'characteristic_ids.*' => ['integer', 'exists:characteristics,id'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/webp,image/heic,image/heif'],
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
            'sighted_at.before_or_equal' => 'The sighting date cannot be in the future.',
            'breed_id.exists' => 'The selected breed does not belong to the specified species.',
        ];
    }
}

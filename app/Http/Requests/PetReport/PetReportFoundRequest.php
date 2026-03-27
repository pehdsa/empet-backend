<?php

namespace App\Http\Requests\PetReport;

use App\Enums\PetSize;
use App\Enums\PetSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PetReportFoundRequest extends FormRequest
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
            'species.in' => 'The species must be one of: DOG, CAT.',
            'size.in' => 'The size must be one of: SMALL, MEDIUM, LARGE.',
        ];
    }
}

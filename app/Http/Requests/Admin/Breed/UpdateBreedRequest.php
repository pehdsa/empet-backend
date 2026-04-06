<?php

namespace App\Http\Requests\Admin\Breed;

use App\Enums\PetSpecies;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBreedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('breeds')
                    ->where('species', $this->input('species'))
                    ->ignore($this->route('breed')),
            ],
            'species' => ['required', Rule::enum(PetSpecies::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nome é obrigatório.',
            'name.unique' => 'Já existe uma raça com esse nome para a espécie selecionada.',
            'species.required' => 'Espécie é obrigatória.',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin\Characteristic;

use App\Enums\CharacteristicCategory;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCharacteristicRequest extends FormRequest
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
                Rule::unique('characteristics')
                    ->where('category', $this->input('category'))
                    ->ignore($this->route('characteristic')),
            ],
            'category' => ['required', Rule::enum(CharacteristicCategory::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nome é obrigatório.',
            'name.unique' => 'Já existe uma característica com esse nome para a categoria selecionada.',
            'category.required' => 'Categoria é obrigatória.',
        ];
    }
}

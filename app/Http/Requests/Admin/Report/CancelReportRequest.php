<?php

namespace App\Http\Requests\Admin\Report;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

final class CancelReportRequest extends FormRequest
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
            'reason' => ['required', 'string', 'min:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'O motivo do cancelamento é obrigatório.',
            'reason.string' => 'O motivo deve ser um texto.',
            'reason.min' => 'O motivo deve ter pelo menos :min caracteres.',
        ];
    }
}

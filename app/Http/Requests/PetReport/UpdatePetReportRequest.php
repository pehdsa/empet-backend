<?php

namespace App\Http\Requests\PetReport;

use App\Enums\PetReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePetReportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'address_hint' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lost_at' => ['sometimes', 'required', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $report = $this->route('petReport');
            if ($report && $report->status !== PetReportStatus::Lost) {
                $validator->errors()->add('status', 'Only reports with LOST status can be updated.');
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
            'lost_at.before_or_equal' => 'The lost date cannot be in the future.',
        ];
    }
}

<?php

namespace App\Http\Requests\PetSighting;

use App\Enums\PetReportStatus;
use App\Models\PetReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address_hint' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sighted_at' => ['required', 'date', 'before_or_equal:now'],
            'share_phone' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var PetReport|null $petReport */
            $petReport = $this->route('petReport');

            if (! $petReport) {
                return;
            }

            if ($petReport->status !== PetReportStatus::Lost) {
                $validator->errors()->add('report', 'Sightings can only be reported for lost pets.');

                return;
            }

            if (! $petReport->is_active) {
                $validator->errors()->add('report', 'This report is no longer active.');

                return;
            }

            if ($petReport->user_id === $this->user()->id) {
                $validator->errors()->add('report', 'You cannot report a sighting of your own pet.');
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
            'sighted_at.before_or_equal' => 'The sighting date cannot be in the future.',
        ];
    }
}

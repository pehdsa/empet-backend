<?php

namespace App\Http\Requests\PetReport;

use App\Enums\PetReportStatus;
use App\Models\Pet;
use App\Models\PetReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePetReportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pet_id' => [
                'required',
                'integer',
                Rule::exists('pets', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address_hint' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lost_at' => ['required', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $petId = $this->input('pet_id');
            if (! $petId || $validator->errors()->has('pet_id')) {
                return;
            }

            $pet = Pet::find($petId);
            if (! $pet) {
                return;
            }

            if ($pet->user_id !== $this->user()->id) {
                $validator->errors()->add('pet_id', 'You can only report your own pets as lost.');

                return;
            }

            $hasActiveReport = PetReport::query()
                ->where('pet_id', $petId)
                ->where('status', PetReportStatus::Lost)
                ->where('is_active', true)
                ->exists();

            if ($hasActiveReport) {
                $validator->errors()->add('pet_id', 'This pet already has an active lost report.');
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
            'pet_id.exists' => 'Pet not found.',
            'lost_at.before_or_equal' => 'The lost date cannot be in the future.',
        ];
    }
}

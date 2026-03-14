<?php

namespace App\Http\Resources;

use App\Models\UserPhone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserPhone
 */
class UserPhoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'isWhatsapp' => $this->is_whatsapp,
            'isPrimary' => $this->is_primary,
            'label' => $this->label,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CharacteristicCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\CharacteristicResource;
use App\Models\Characteristic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CharacteristicController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'category' => ['nullable', 'string', Rule::in(array_column(CharacteristicCategory::cases(), 'value'))],
        ]);

        $query = Characteristic::query()->where('is_active', true);

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $characteristics = $query->orderBy('name')->get();

        return CharacteristicResource::collection($characteristics);
    }
}

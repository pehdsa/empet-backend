<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Breed\BreedIndexRequest;
use App\Http\Resources\BreedResource;
use App\Models\Breed;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BreedController extends Controller
{
    public function index(BreedIndexRequest $request): AnonymousResourceCollection
    {
        $query = Breed::query()->where('is_active', true);

        if ($request->validated('species')) {
            $query->where('species', $request->validated('species'));
        }

        $breeds = $query->orderBy('name')->get();

        return BreedResource::collection($breeds);
    }
}

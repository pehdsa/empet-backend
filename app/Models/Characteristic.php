<?php

namespace App\Models;

use App\Enums\CharacteristicCategory;
use Database\Factories\CharacteristicFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Characteristic extends Model
{
    /** @use HasFactory<CharacteristicFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'category',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => CharacteristicCategory::class,
            'is_active' => 'boolean',
        ];
    }

    public function pets(): BelongsToMany
    {
        return $this->belongsToMany(Pet::class, 'pet_characteristics')
            ->withPivot('created_at');
    }
}

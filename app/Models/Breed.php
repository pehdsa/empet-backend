<?php

namespace App\Models;

use App\Enums\PetSpecies;
use Database\Factories\BreedFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Breed extends Model
{
    /** @use HasFactory<BreedFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'species',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'species' => PetSpecies::class,
            'is_active' => 'boolean',
        ];
    }

    public function petsAsPrimary(): HasMany
    {
        return $this->hasMany(Pet::class, 'breed_id');
    }

    public function petsAsSecondary(): HasMany
    {
        return $this->hasMany(Pet::class, 'secondary_breed_id');
    }
}

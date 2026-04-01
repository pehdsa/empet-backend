<?php

namespace App\Models;

use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PetSighting extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'address_hint',
        'sighted_at',
        'species',
        'size',
        'sex',
        'color',
        'breed_id',
        'share_phone',
        'location',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'species' => PetSpecies::class,
            'size' => PetSize::class,
            'sex' => PetSex::class,
            'sighted_at' => 'datetime',
            'share_phone' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PetSightingPhoto::class)->orderBy('position');
    }

    public function characteristics(): BelongsToMany
    {
        return $this->belongsToMany(Characteristic::class, 'pet_sighting_characteristics');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(PetMatch::class, 'sighting_id');
    }

    /**
     * Scope to select latitude and longitude from the geography column.
     */
    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->select('pet_sightings.*')
            ->selectRaw('ST_Y(location::geometry) as latitude, ST_X(location::geometry) as longitude');
    }
}

<?php

namespace App\Models;

use App\Enums\PetReportStatus;
use App\Enums\PetSex;
use App\Enums\PetSize;
use App\Enums\PetSpecies;
use Database\Factories\PetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pet extends Model
{
    /** @use HasFactory<PetFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'species',
        'size',
        'sex',
        'breed_id',
        'secondary_breed_id',
        'breed_description',
        'primary_color',
        'notes',
        'is_active',
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
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class, 'breed_id');
    }

    public function secondaryBreed(): BelongsTo
    {
        return $this->belongsTo(Breed::class, 'secondary_breed_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PetPhoto::class)->orderBy('position');
    }

    public function characteristics(): BelongsToMany
    {
        return $this->belongsToMany(Characteristic::class, 'pet_characteristics')
            ->withPivot('created_at');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PetReport::class);
    }

    public function activeReport(): HasOne
    {
        return $this->hasOne(PetReport::class)->where('status', PetReportStatus::Lost);
    }
}

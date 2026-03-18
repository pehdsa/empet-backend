<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PetSighting extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'report_id',
        'location',
        'address_hint',
        'description',
        'sighted_at',
        'share_phone',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sighted_at' => 'datetime',
            'is_active' => 'boolean',
            'share_phone' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(PetReport::class, 'report_id');
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

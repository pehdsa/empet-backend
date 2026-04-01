<?php

namespace App\Models;

use App\Enums\PetReportStatus;
use Database\Factories\PetReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PetReport extends Model
{
    /** @use HasFactory<PetReportFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pet_id',
        'user_id',
        'status',
        'location',
        'address_hint',
        'description',
        'lost_at',
        'found_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PetReportStatus::class,
            'lost_at' => 'datetime',
            'found_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(PetMatch::class, 'report_id');
    }

    public function reportSightings(): HasMany
    {
        return $this->hasMany(PetReportSighting::class, 'report_id');
    }

    /**
     * Scope to select latitude and longitude from the geography column.
     */
    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->select('pet_reports.*')
            ->selectRaw('ST_Y(location::geometry) as latitude, ST_X(location::geometry) as longitude');
    }
}

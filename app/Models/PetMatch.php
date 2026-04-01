<?php

namespace App\Models;

use App\Enums\PetMatchStatus;
use Database\Factories\PetMatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetMatch extends Model
{
    /** @use HasFactory<PetMatchFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'report_id',
        'sighting_id',
        'score',
        'distance_meters',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PetMatchStatus::class,
            'score' => 'decimal:2',
            'distance_meters' => 'decimal:2',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(PetReport::class, 'report_id')->withTrashed();
    }

    public function sighting(): BelongsTo
    {
        return $this->belongsTo(PetSighting::class, 'sighting_id')->withTrashed();
    }
}

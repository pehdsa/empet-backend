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
        'base_score',
        'ai_score',
        'ai_confidence',
        'ai_status',
        'ai_provider',
        'ai_model',
        'ai_summary',
        'ai_evaluated_at',
        'final_score',
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
            'base_score' => 'decimal:2',
            'ai_score' => 'decimal:2',
            'ai_confidence' => 'decimal:3',
            'ai_evaluated_at' => 'datetime',
            'final_score' => 'decimal:2',
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

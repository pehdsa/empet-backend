<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetSightingClaim extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'pet_sighting_id',
        'user_id',
    ];

    public function sighting(): BelongsTo
    {
        return $this->belongsTo(PetSighting::class, 'pet_sighting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

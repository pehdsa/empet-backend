<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationSetting extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'notify_lost_nearby',
        'notify_matches',
        'notify_sightings',
        'nearby_radius_km',
        'location',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notify_lost_nearby' => 'boolean',
            'notify_matches' => 'boolean',
            'notify_sightings' => 'boolean',
            'nearby_radius_km' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to select latitude and longitude from the geography column.
     */
    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->select('user_notification_settings.*')
            ->selectRaw('ST_Y(location::geometry) as latitude, ST_X(location::geometry) as longitude');
    }
}

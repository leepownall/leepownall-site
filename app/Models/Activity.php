<?php

namespace App\Models;

use App\Observers\ActivityObserver;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([ActivityObserver::class])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'strava_id',
        'name',
        'started_at',
        'distance_metres',
        'moving_time_seconds',
    ];

    /**
     * Whether an activity of this Strava sport type, from either the export
     * ("Trail Run") or the API ("TrailRun"), is a run long enough to keep.
     */
    public static function isKeptRun(string $sportType, int $distanceMetres, int $movingTimeSeconds): bool
    {
        $sport = strtolower(str_replace(' ', '', trim($sportType)));

        return in_array($sport, ['run', 'trailrun', 'virtualrun'], true)
            && $distanceMetres >= 100
            && $movingTimeSeconds >= 60;
    }

    public function paceSecondsPerKilometre(): int
    {
        return (int) round($this->moving_time_seconds / ($this->distance_metres / 1000));
    }

    public function movingMinutes(): int
    {
        return (int) round($this->moving_time_seconds / 60);
    }

    public function url(): string
    {
        return "https://www.strava.com/activities/{$this->strava_id}";
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\StravaToken;
use App\Training\StravaClient;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class SyncStravaActivity implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public int $athleteId, public int $stravaId) {}

    /**
     * Store the activity as it is on Strava now. A run that was deleted,
     * made private to another athlete, or changed to another sport is removed.
     */
    public function handle(): void
    {
        $token = StravaToken::query()->where('athlete_id', $this->athleteId)->first();

        if ($token === null) {
            return;
        }

        $activity = StravaClient::activity($token, $this->stravaId);
        $distance = (int) round((float) ($activity['distance'] ?? 0));
        $movingTime = (int) ($activity['moving_time'] ?? 0);

        if ($activity === null || ! Activity::isKeptRun((string) ($activity['sport_type'] ?? $activity['type'] ?? ''), $distance, $movingTime)) {
            Activity::query()->where('strava_id', $this->stravaId)->get()->each->delete();

            return;
        }

        Activity::query()->updateOrCreate(['strava_id' => $this->stravaId], [
            'name' => Str::limit(trim((string) ($activity['name'] ?? 'Run')), 255, ''),
            'started_at' => CarbonImmutable::parse($activity['start_date'])->utc()->toDateTimeString(),
            'distance_metres' => $distance,
            'moving_time_seconds' => $movingTime,
        ]);
    }
}

<?php

namespace App\Jobs\Strava;

use App\Models\Activity;
use App\Services\StravaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;

class ProcessWebhookJob extends SpatieProcessWebhookJob implements ShouldQueue
{
    public function handle(): void
    {
        Log::debug('Processing webhook', [
            'payload' => $this->webhookCall->payload,
        ]);

        $strava = new StravaService;

        $detailedActivity = $strava->activity(stravaActivityId: $this->webhookCall->payload['object_id']);

        Activity::updateOrCreate(
            attributes: [
                'activity_id' => $detailedActivity->activityId,
            ],
            values: [
                'name' => $detailedActivity->name,
                'type' => $detailedActivity->type,
                'distance' => $detailedActivity->distance,
                'moving_time' => $detailedActivity->movingTime,
                'elapsed_time' => $detailedActivity->elapsedTime,
                'total_elevation_gain' => $detailedActivity->totalElevationGain,
                'started_at' => $detailedActivity->startedAt,
                'path' => $detailedActivity->path,
                'max_elevation' => $detailedActivity->maxElevation,
                'min_elevation' => $detailedActivity->minElevation,
                'description' => $detailedActivity->description,
            ]
        );
    }
}

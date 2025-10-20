<?php

namespace App\Jobs\Strava\Verify;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;

class ProcessWebhookJob extends SpatieProcessWebhookJob implements ShouldQueue
{
    public function handle(): void
    {
        Log::info('Verifying webhook job', [
            'payload' => $this->webhookCall->payload,
        ]);
    }
}

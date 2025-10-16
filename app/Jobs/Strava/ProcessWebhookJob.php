<?php

namespace App\Jobs\Strava;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;

class ProcessWebhookJob extends SpatieProcessWebhookJob implements ShouldQueue
{
    public function handle()
    {
        Log::debug('Processing webhook', [
            'payload' => $this->webhookCall->payload,
        ]);
    }
}

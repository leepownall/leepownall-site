<?php

namespace App\Jobs\Strava;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;

class ProcessWebhookJob extends SpatieProcessWebhookJob implements ShouldQueue
{
    public function handle()
    {
        // $this->webhookCall // contains an instance of `WebhookCall`

        // perform the work here
    }
}

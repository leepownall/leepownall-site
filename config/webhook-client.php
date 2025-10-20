<?php

return [
    'configs' => [
        [
            'name' => 'strava-verify',
            'signature_validator' => \App\Http\Integrations\Strava\Verify\SignatureValidator::class,
            'webhook_profile' => \App\Http\Integrations\Strava\Verify\ProcessWebhookProfile::class,
            'webhook_response' => \App\Http\Integrations\Strava\Verify\RespondsToWebhook::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'process_webhook_job' => \App\Jobs\Strava\Verify\ProcessWebhookJob::class,
        ],
        [
            'name' => 'strava',
            'signature_validator' => \App\Http\Integrations\Strava\SignatureValidator::class,
            'webhook_profile' => \App\Http\Integrations\Strava\ProcessWebhookProfile::class,
            'webhook_response' => \App\Http\Integrations\Strava\RespondsToWebhook::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'process_webhook_job' => \App\Jobs\Strava\ProcessWebhookJob::class,
        ],
    ],
    'delete_after_days' => 30,
    'add_unique_token_to_route_name' => false,
];

<?php

return [
    'configs' => [
        [
            'name' => 'strava',
            'signing_secret' => env('WEBHOOK_CLIENT_SECRET'),
            'signature_header_name' => 'Signature',
            'signature_validator' => \App\Http\Integrations\Strava\SignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \App\Http\Integrations\Strava\RespondsToWebhook::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [],
            'process_webhook_job' => \App\Jobs\Strava\ProcessWebhookJob::class,
        ],
    ],
    'delete_after_days' => 30,
    'add_unique_token_to_route_name' => false,
];

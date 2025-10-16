<?php

declare(strict_types=1);

namespace App\Http\Integrations\Strava;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookResponse\RespondsToWebhook as SpatieProcessWebhookResponse;
use Symfony\Component\HttpFoundation\Response;

class RespondsToWebhook implements SpatieProcessWebhookResponse
{
    public function respondToValidWebhook(Request $request, WebhookConfig $config): Response
    {
        if ($request->has('hub_challenge')) {
            return response()->json(['hub.challenge' => $request->get('hub_challenge')]);
        }

        return response()->json();
    }
}

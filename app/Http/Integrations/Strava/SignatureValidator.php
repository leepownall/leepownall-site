<?php

declare(strict_types=1);

namespace App\Http\Integrations\Strava;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator as SpatieSignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class SignatureValidator implements SpatieSignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        return $request->get('hub_verify_token') === Config::string('services.strava.verify_token');
    }
}

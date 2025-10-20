<?php

declare(strict_types=1);

namespace App\Http\Integrations\Strava;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator as SpatieSignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class SignatureValidator implements SpatieSignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        return true;
    }
}

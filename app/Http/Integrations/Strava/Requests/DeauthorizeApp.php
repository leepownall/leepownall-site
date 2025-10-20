<?php

declare(strict_types=1);

namespace App\Http\Integrations\Strava\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeauthorizeApp extends Request
{
    protected Method $method = Method::POST;

    public function __construct(private readonly string $accessToken) {}

    public function resolveEndpoint(): string
    {
        return '/oauth/deauthorize';
    }

    public function defaultQuery(): array
    {
        return [
            'access_token' => $this->accessToken,
        ];
    }
}

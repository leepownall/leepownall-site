<?php

declare(strict_types=1);

namespace App\Http\Integrations\Strava\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class RefreshAccessToken extends Request
{
    protected Method $method = Method::POST;

    public function __construct(protected string $refreshToken) {}

    public function resolveEndpoint(): string
    {
        return '/oauth/token';
    }

    protected function defaultQuery(): array
    {
        return [
            'client_id' => config('services.strava.client_id'),
            'client_secret' => config('services.strava.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
        ];
    }
}

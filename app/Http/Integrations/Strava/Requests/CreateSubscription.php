<?php

declare(strict_types=1);

namespace App\Http\Integrations\Strava\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class CreateSubscription extends Request
{
    protected Method $method = Method::POST;

    public function __construct(protected string $domain) {}

    public function resolveEndpoint(): string
    {
        return '/push_subscriptions';
    }

    protected function defaultQuery(): array
    {
        return [
            'client_id' => config('services.strava.client_id'),
            'client_secret' => config('services.strava.client_secret'),
            'callback_url' => "{$this->domain}/api/webhook",
            'verify_token' => 'STRAVA',
        ];
    }
}

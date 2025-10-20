<?php

declare(strict_types=1);

namespace App\Http\Integrations\Strava\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteSubscription extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(protected int $pushSubscriptionId) {}

    public function resolveEndpoint(): string
    {
        return "/push_subscriptions/{$this->pushSubscriptionId}";
    }

    protected function defaultQuery(): array
    {
        return [
            'client_id' => config('services.strava.client_id'),
            'client_secret' => config('services.strava.client_secret'),
        ];
    }
}

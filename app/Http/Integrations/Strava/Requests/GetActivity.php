<?php

declare(strict_types=1);

namespace App\Http\Integrations\Strava\Requests;

use App\DataTransferObjects\Strava\DetailedActivity;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetActivity extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private int $id) {}

    public function resolveEndpoint(): string
    {
        return "/activities/{$this->id}";
    }

    public function createDtoFromResponse(Response $response): DetailedActivity
    {
        return DetailedActivity::fromResponse($response->json());
    }
}

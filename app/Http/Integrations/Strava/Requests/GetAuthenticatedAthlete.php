<?php

declare(strict_types=1);

namespace App\Http\Integrations\Strava\Requests;

use App\DataTransferObjects\Strava\DetailedAthlete;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetAuthenticatedAthlete extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/athlete';
    }

    public function createDtoFromResponse(Response $response): DetailedAthlete
    {
        return DetailedAthlete::fromResponse($response->json());
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Integrations\Strava;

use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\HasPagination;
use Saloon\PaginationPlugin\PagedPaginator;
use Saloon\Traits\OAuth2\AuthorizationCodeGrant;
use Saloon\Traits\Plugins\AcceptsJson;

class StravaConnector extends Connector implements HasPagination
{
    use AcceptsJson;
    use AuthorizationCodeGrant;

    public function resolveBaseUrl(): string
    {
        return 'https://www.strava.com/api/v3';
    }

    protected function defaultOauthConfig(): OAuthConfig
    {
        return OAuthConfig::make()
            ->setClientId(config('services.strava.client_id'))
            ->setClientSecret(config('services.strava.client_secret'))
            ->setDefaultScopes(config('services.strava.default_scopes'))
            ->setRedirectUri(config('services.strava.redirect_uri'))
            ->setAuthorizeEndpoint(config('services.strava.authorize_endpoint'))
            ->setTokenEndpoint(config('services.strava.token_endpoint'))
            ->setUserEndpoint('/athlete');
    }

    public function paginate(Request $request): PagedPaginator
    {
        return new class(connector: $this, request: $request) extends PagedPaginator
        {
            protected ?int $perPageLimit = 200;

            protected function isLastPage(Response $response): bool
            {
                return empty($response->json());
            }

            protected function getPageItems(
                Response $response,
                Request $request
            ): array {
                return $response->dtoOrFail()->toArray();
            }
        };
    }
}

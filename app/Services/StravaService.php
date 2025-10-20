<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\Strava\AccessTokenDetails;
use App\DataTransferObjects\Strava\AuthorizationCallbackDetails;
use App\DataTransferObjects\Strava\AuthorizationRedirectDetails;
use App\DataTransferObjects\Strava\DetailedActivity;
use App\DataTransferObjects\Strava\DetailedAthlete;
use App\Http\Integrations\Strava\Requests\GetActivity;
use App\Http\Integrations\Strava\Requests\GetAuthenticatedAthlete;
use App\Http\Integrations\Strava\StravaConnector;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Auth\AccessTokenAuthenticator;

final readonly class StravaService
{
    private function connector(): StravaConnector
    {
        return new StravaConnector;
    }

    public function authenticatedAthlete(): DetailedAthlete
    {
        return $this
            ->connectorForUser()
            ->send(new GetAuthenticatedAthlete)
            ->dtoOrFail();
    }

    public function activity(int $stravaActivityId): DetailedActivity
    {
        return $this
            ->connectorForUser()
            ->send(new GetActivity(id: $stravaActivityId))
            ->dtoOrFail();
    }

    private function getOAuthDetails(): AccessTokenAuthenticator
    {
        return new AccessTokenAuthenticator(
            Cache::get('strava_access_token'),
            Cache::get('refresh_token'),
            Cache::get('refresh_token_expires_at'),
        );
    }

    private function connectorForUser(): StravaConnector
    {
        $accessTokenDetails = $this->getOAuthDetails();

        $connector = $this->connector()->authenticate($accessTokenDetails);

        if ($accessTokenDetails->hasExpired()) {
            $newAccessTokenDetails = $connector->refreshAccessToken($accessTokenDetails);

            $connector->authenticate($newAccessTokenDetails);
            $this->updateOAuthDetails($newAccessTokenDetails);
        }

        return $connector;
    }

    private function updateOAuthDetails(AccessTokenAuthenticator $newAccessTokenDetails): void
    {
        Cache::put('strava_access_token', $newAccessTokenDetails->accessToken);
        Cache::put('refresh_token', $newAccessTokenDetails->refreshToken);
        Cache::put('refresh_token_expires_at', $newAccessTokenDetails->expiresAt);
    }

    public function getAuthRedirectDetails(): AuthorizationRedirectDetails
    {
        $connector = $this->connector();

        $authorizationUrl = $connector->getAuthorizationUrl();

        return new AuthorizationRedirectDetails(
            authorizationUrl: $authorizationUrl,
            state: $connector->getState(),
        );
    }

    public function authorize(AuthorizationCallbackDetails $callbackDetails): AccessTokenDetails
    {
        $tokenDetails = $this->connector()->getAccessToken(
            code: $callbackDetails->authorizationCode,
            state: $callbackDetails->state,
        );

        return new AccessTokenDetails(
            accessToken: $tokenDetails->accessToken,
            refreshToken: $tokenDetails->refreshToken,
            expiresAt: $tokenDetails->expiresAt,
        );
    }
}

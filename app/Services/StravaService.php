<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\Strava\AuthorizationCallbackDetails;
use App\DataTransferObjects\Strava\AuthorizationRedirectDetails;
use App\DataTransferObjects\Strava\AccessTokenDetails;
use App\DataTransferObjects\Strava\DetailedActivity;
use App\DataTransferObjects\Strava\DetailedAthlete;
use App\Http\Integrations\Strava\Requests\GetActivity;
use App\Http\Integrations\Strava\Requests\GetAuthenticatedAthlete;
use App\Http\Integrations\Strava\StravaConnector;
use App\Models\User;
use Saloon\Http\Auth\AccessTokenAuthenticator;

final readonly class StravaService
{
    private function connector(): StravaConnector
    {
        return new StravaConnector;
    }

    public function authenticatedAthlete(User $user): DetailedAthlete
    {
        return $this
            ->connectorForUser($user)
            ->send(new GetAuthenticatedAthlete)
            ->dtoOrFail();
    }

    public function activity(User $user, int $stravaActivityId): DetailedActivity
    {
        return $this
            ->connectorForUser($user)
            ->send(new GetActivity(id: $stravaActivityId))
            ->dtoOrFail();
    }

    private function getOAuthDetails(User $user): AccessTokenAuthenticator
    {
        $integration = $user->stravaIntegration->first();

        return new AccessTokenAuthenticator(
            $integration->access_token,
            $integration->refresh_token,
            $integration->refresh_token_expires_at->toDateTimeImmutable(),
        );
    }

    private function connectorForUser(User $user): StravaConnector
    {
        $accessTokenDetails = $this->getOAuthDetails($user);

        $connector = $this->connector()->authenticate($accessTokenDetails);

        if ($accessTokenDetails->hasExpired()) {
            $newAccessTokenDetails = $connector->refreshAccessToken($accessTokenDetails);

            $connector->authenticate($newAccessTokenDetails);
            $this->updateOAuthDetails($newAccessTokenDetails, $user);
        }

        return $connector;
    }

    private function updateOAuthDetails(AccessTokenAuthenticator $newAccessTokenDetails, User $user): void
    {
        $user->updateOAuthDetails(
            $newAccessTokenDetails->getAccessToken(),
            $newAccessTokenDetails->getRefreshToken(),
            $newAccessTokenDetails->getExpiresAt(),
        );
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

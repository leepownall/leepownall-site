<?php

namespace App\Training;

use App\Models\StravaToken;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Every call this site makes to the Strava API.
 */
final class StravaClient
{
    private const string URL = 'https://www.strava.com';

    /**
     * Exchange the code from Strava's authorisation page for tokens.
     *
     * @return array{access_token: string, refresh_token: string, expires_at: int, athlete: array{id: int}}
     */
    public static function exchangeCode(string $code): array
    {
        return self::token(['grant_type' => 'authorization_code', 'code' => $code]);
    }

    /**
     * An access token that is valid for at least another minute, refreshing
     * it, and storing the rotated refresh token, when it is about to expire.
     */
    public static function accessToken(StravaToken $token): string
    {
        if ($token->expires_at->isAfter(now()->addMinute())) {
            return $token->access_token;
        }

        $response = self::token(['grant_type' => 'refresh_token', 'refresh_token' => $token->refresh_token]);

        return StravaToken::store($token->athlete_id, $response)->access_token;
    }

    /**
     * The activity as it is on Strava now, or null when it no longer exists
     * or the athlete can no longer see it.
     *
     * @return array<string, mixed>|null
     */
    public static function activity(StravaToken $token, int $stravaId): ?array
    {
        $response = self::api($token)->get("/api/v3/activities/{$stravaId}");

        return $response->notFound() ? null : $response->throw()->json();
    }

    /**
     * Every route the athlete has saved, rides and runs.
     *
     * @return list<array<string, mixed>>
     */
    public static function routes(StravaToken $token): array
    {
        $routes = [];
        $page = 1;

        do {
            $batch = self::api($token)
                ->get("/api/v3/athletes/{$token->athlete_id}/routes", ['page' => $page++, 'per_page' => 200])
                ->throw()
                ->json();

            array_push($routes, ...$batch);
        } while (count($batch) === 200);

        return $routes;
    }

    /**
     * Ask Strava to send activity webhooks to the callback URL.
     */
    public static function subscribe(string $callbackUrl): void
    {
        Http::asForm()
            ->post(self::URL.'/api/v3/push_subscriptions', [
                ...self::credentials(),
                'callback_url' => $callbackUrl,
                'verify_token' => config('services.strava.verify_token'),
            ])
            ->throw();
    }

    public static function authorizeUrl(string $redirectUri): string
    {
        return self::URL.'/oauth/authorize?'.http_build_query([
            'client_id' => config('services.strava.client_id'),
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'approval_prompt' => 'force',
            'scope' => 'read_all,activity:read_all',
        ]);
    }

    private static function api(StravaToken $token): PendingRequest
    {
        return Http::baseUrl(self::URL)->withToken(self::accessToken($token));
    }

    /**
     * @param  array<string, string>  $grant
     * @return array{access_token: string, refresh_token: string, expires_at: int, athlete?: array{id: int}}
     */
    private static function token(array $grant): array
    {
        return Http::asForm()
            ->post(self::URL.'/oauth/token', [...self::credentials(), ...$grant])
            ->throw()
            ->json();
    }

    /**
     * @return array{client_id: mixed, client_secret: mixed}
     */
    private static function credentials(): array
    {
        return [
            'client_id' => config('services.strava.client_id'),
            'client_secret' => config('services.strava.client_secret'),
        ];
    }
}

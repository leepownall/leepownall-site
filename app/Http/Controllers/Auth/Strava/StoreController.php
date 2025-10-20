<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Strava;

use App\DataTransferObjects\Strava\AuthorizationCallbackDetails;
use App\Services\StravaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class StoreController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $callbackDetails = new AuthorizationCallbackDetails(
            authorizationCode: $request->input('code'),
            state: $request->input('state'),
        );

        $stravaService = new StravaService;

        $accessTokenDetails = $stravaService->authorize($callbackDetails);

        Cache::put('strava_access_token', $accessTokenDetails->accessToken);
        Cache::put('refresh_token', $accessTokenDetails->refreshToken);
        Cache::put('refresh_token_expires_at', $accessTokenDetails->expiresAt);

        return redirect()->intended();
    }
}

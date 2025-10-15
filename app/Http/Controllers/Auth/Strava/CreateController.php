<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Strava;

use App\Services\StravaService;
use Illuminate\Http\RedirectResponse;

final class CreateController
{
    public function __invoke(): RedirectResponse
    {
        $redirectDetails = (new StravaService)->getAuthRedirectDetails();

        return redirect()->to($redirectDetails->authorizationUrl);
    }
}

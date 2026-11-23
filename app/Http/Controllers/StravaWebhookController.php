<?php

namespace App\Http\Controllers;

use App\Jobs\SyncStravaActivity;
use App\Models\StravaToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StravaWebhookController extends Controller
{
    /**
     * Answer Strava's check when the push subscription is created.
     */
    public function verify(Request $request): JsonResponse
    {
        $token = (string) config('services.strava.verify_token');

        abort_unless(
            $request->query('hub_mode') === 'subscribe'
                && $token !== ''
                && hash_equals($token, (string) $request->query('hub_verify_token')),
            403,
        );

        return response()->json(['hub.challenge' => $request->query('hub_challenge')]);
    }

    /**
     * Strava sends only the activity ID, so the job fetches the activity.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $athleteId = $request->integer('owner_id');

        if ($request->input('object_type') === 'activity'
            && StravaToken::query()->where('athlete_id', $athleteId)->exists()) {
            SyncStravaActivity::dispatch($athleteId, $request->integer('object_id'));
        }

        return response()->json(status: 200);
    }
}

<?php

namespace App\Training;

use App\Models\StravaRoute;
use App\Models\StravaToken;
use Illuminate\Support\Str;
use RuntimeException;

final class StravaRoutes
{
    /**
     * Copy the connected athlete's running routes from Strava, removing
     * routes that were deleted there, and return how many were kept.
     *
     * @throws RuntimeException when Strava is not connected
     */
    public static function sync(): int
    {
        $token = StravaToken::query()->first() ?? throw new RuntimeException('Strava is not connected. Run strava:connect first.');
        $kept = [];

        foreach (StravaClient::routes($token) as $route) {
            if (($route['type'] ?? null) !== 2) {
                continue;
            }

            $name = Str::limit(trim((string) $route['name']), 255, '');
            $description = trim((string) ($route['description'] ?? '')) ?: null;

            StravaRoute::query()->updateOrCreate(['strava_id' => (string) $route['id_str']], [
                'name' => $name,
                'description' => $description,
                'distance_metres' => (int) round((float) $route['distance']),
                'elevation_gain_metres' => (int) round((float) ($route['elevation_gain'] ?? 0)),
                'time' => RouteTime::fromStrava($name, $description),
            ]);

            $kept[] = (string) $route['id_str'];
        }

        StravaRoute::query()->whereNotIn('strava_id', $kept)->delete();

        return count($kept);
    }
}

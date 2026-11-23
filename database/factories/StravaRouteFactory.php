<?php

namespace Database\Factories;

use App\Models\StravaRoute;
use App\Training\RouteTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StravaRoute>
 */
class StravaRouteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'strava_id' => (string) fake()->unique()->numberBetween(1_000_000_000, 9_999_999_999),
            'name' => 'Canal loop',
            'description' => null,
            'distance_metres' => 8000,
            'elevation_gain_metres' => 40,
            'time' => RouteTime::Daytime,
        ];
    }

    public function nighttime(): static
    {
        return $this->state(['name' => 'Town loop (lit)', 'time' => RouteTime::Nighttime]);
    }
}

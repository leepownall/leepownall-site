<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'strava_id' => fake()->unique()->numberBetween(1, 99999999),
            'name' => 'Easy run',
            'started_at' => now(),
            'distance_metres' => 6000,
            'moving_time_seconds' => 2400,
        ];
    }
}

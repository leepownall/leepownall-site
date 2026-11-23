<?php

namespace Database\Factories;

use App\Models\StravaToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StravaToken>
 */
class StravaTokenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'athlete_id' => 42,
            'access_token' => 'access',
            'refresh_token' => 'refresh',
            'expires_at' => now()->addHours(6),
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subMinute()]);
    }
}

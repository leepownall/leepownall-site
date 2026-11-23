<?php

namespace Database\Factories;

use App\Models\ScheduledSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduledSession>
 */
class ScheduledSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->unique()->dateTimeBetween('2026-10-05', '2027-04-25')->format('Y-m-d'),
            'kind' => 'plan',
            'week_index' => 1,
            'template' => 1,
            'phase' => 'Prep',
            'is_rest' => false,
            'name' => 'Easy run',
            'lines' => ['6 km'],
            'has_strength' => false,
            'estimated_minutes' => null,
        ];
    }
}

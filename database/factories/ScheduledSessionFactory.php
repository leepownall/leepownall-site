<?php

namespace Database\Factories;

use App\Models\ScheduledSession;
use App\Models\TrainingWeek;
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
            'training_week_id' => TrainingWeek::factory(),
            'date' => '2027-01-11',
            'is_rest' => false,
            'name' => 'Easy run',
            'lines' => ['6 km'],
            'has_strength' => false,
            'estimated_minutes' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Race;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Race>
 */
class RaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Stabridge Stagger',
            'date' => '2027-01-24',
            'distance_metres' => 16093,
            'is_target' => false,
        ];
    }

    public function target(): static
    {
        return $this->state(['name' => 'Manchester Marathon', 'date' => '2027-04-25', 'distance_metres' => 42195, 'is_target' => true]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'activity_id' => $this->faker->randomNumber(),
            'name' => $this->faker->name(),
            'type' => $this->faker->word(),
            'distance' => $this->faker->randomFloat(),
            'moving_time' => $this->faker->randomNumber(),
            'elapsed_time' => $this->faker->randomNumber(),
            'total_elevation_gain' => $this->faker->randomFloat(),
            'started_at' => Carbon::now(),
            'path' => $this->faker->word(),
            'max_elevation' => $this->faker->randomFloat(),
            'min_elevation' => $this->faker->randomFloat(),
            'description' => $this->faker->text(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

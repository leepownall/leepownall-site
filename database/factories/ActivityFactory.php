<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'activity_id' => fake()->unique()->numberBetween(1, 9_999_999),
            'name' => fake()->sentence(3),
            'type' => fake()->randomElement(array_map(static fn (ActivityType $t) => $t->value, ActivityType::cases())),
            'distance' => fake()->randomFloat(2, 0, 50_000),
            'moving_time' => fake()->numberBetween(60, 4 * 3600),
            'elapsed_time' => fake()->numberBetween(60, 5 * 3600),
            'total_elevation_gain' => fake()->randomFloat(2, 0, 1000),
            'started_at' => Carbon::now()->subDays(fake()->numberBetween(0, 30)),
            'path' => null,
            'max_elevation' => fake()->randomFloat(2, 0, 2000),
            'min_elevation' => fake()->randomFloat(2, 0, 2000),
            'description' => fake()->optional()->text(),
        ];
    }

    public function run(): self
    {
        return $this->state(function () {
            return [
                'type' => ActivityType::Run->value,
                'distance' => fake()->randomFloat(2, 500, 50_000),
                'total_elevation_gain' => fake()->randomFloat(2, 0, 1000),
            ];
        });
    }

    public function weightTraining(): self
    {
        return $this->state(function () {
            return [
                'type' => ActivityType::WeightTraining->value,
                'distance' => null,
                'total_elevation_gain' => null,
            ];
        });
    }
}

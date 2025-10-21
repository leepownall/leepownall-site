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
            'activity_id' => $this->faker->unique()->numberBetween(1, 9_999_999),
            'name' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(array_map(static fn (ActivityType $t) => $t->value, ActivityType::cases())),
            // Distances in meters; may be null for non-distance activities
            'distance' => $this->faker->randomFloat(2, 0, 50_000),
            'moving_time' => $this->faker->numberBetween(60, 4 * 3600),
            'elapsed_time' => $this->faker->numberBetween(60, 5 * 3600),
            // Elevation gain in meters
            'total_elevation_gain' => $this->faker->randomFloat(2, 0, 1000),
            'started_at' => Carbon::now()->subDays($this->faker->numberBetween(0, 30)),
            'path' => null,
            'max_elevation' => $this->faker->randomFloat(2, 0, 2000),
            'min_elevation' => $this->faker->randomFloat(2, 0, 2000),
            'description' => $this->faker->optional()->text(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    public function run(): self
    {
        return $this->state(function () {
            return [
                'type' => ActivityType::Run->value,
                // Ensure sensible non-null metrics for runs
                'distance' => $this->faker->randomFloat(2, 500, 50_000),
                'total_elevation_gain' => $this->faker->randomFloat(2, 0, 1000),
            ];
        });
    }

    public function weightTraining(): self
    {
        return $this->state(function () {
            return [
                'type' => ActivityType::WeightTraining->value,
                // Typically N/A for weight training
                'distance' => null,
                'total_elevation_gain' => null,
            ];
        });
    }
}

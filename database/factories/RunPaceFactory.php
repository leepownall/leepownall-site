<?php

namespace Database\Factories;

use App\Models\RunPace;
use App\Training\RunEffort;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RunPace>
 */
class RunPaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'effort' => RunEffort::Easy,
            'seconds_per_kilometre' => 400,
        ];
    }
}

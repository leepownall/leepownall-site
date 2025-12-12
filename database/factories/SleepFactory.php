<?php

namespace Database\Factories;

use App\Models\Sleep;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SleepFactory extends Factory
{
    protected $model = Sleep::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

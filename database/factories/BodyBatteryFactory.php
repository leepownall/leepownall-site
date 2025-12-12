<?php

namespace Database\Factories;

use App\Models\BodyBattery;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BodyBatteryFactory extends Factory
{
    protected $model = BodyBattery::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

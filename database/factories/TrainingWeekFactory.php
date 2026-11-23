<?php

namespace Database\Factories;

use App\Models\TrainingWeek;
use App\Training\WeekKind;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingWeek>
 */
class TrainingWeekFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'starts_on' => '2027-01-11',
            'kind' => WeekKind::Plan,
            'number' => 1,
            'phase' => 'Prep',
        ];
    }
}

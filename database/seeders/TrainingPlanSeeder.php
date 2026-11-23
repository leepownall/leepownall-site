<?php

namespace Database\Seeders;

use App\Training\MarathonPlan;
use App\Training\WeekKind;
use Illuminate\Database\Seeder;

class TrainingPlanSeeder extends Seeder
{
    /**
     * A two week build-up then a four week plan, starting this Monday, in
     * the shape the MCP import takes.
     */
    public function run(): void
    {
        $monday = now()->startOfWeek();
        $weeks = [];

        foreach ([WeekKind::Prep, WeekKind::Prep, WeekKind::Plan, WeekKind::Plan, WeekKind::Plan, WeekKind::Plan] as $index => $kind) {
            $weeks[] = [
                'starts_on' => $monday->addWeeks($index)->toDateString(),
                'kind' => $kind->value,
                'phase' => $kind === WeekKind::Prep ? 'Build-up' : ($index < 5 ? 'Build' : 'Taper'),
                'days' => [
                    ['name' => 'Easy run', 'lines' => ['6 km']],
                    ['rest' => true],
                    ['name' => 'Tempo run', 'lines' => ['7 km', '2 km easy', '3 km tempo', '2 km easy']],
                    ['strength' => true],
                    ['rest' => true],
                    ['name' => 'Long run', 'lines' => [(12 + $index * 2).' km', (8 + $index * 2).' km easy', '4 km marathon pace']],
                    ['rest' => true],
                ],
            ];
        }

        MarathonPlan::replace($weeks);
    }
}

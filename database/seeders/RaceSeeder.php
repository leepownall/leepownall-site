<?php

namespace Database\Seeders;

use App\Models\Race;
use Illuminate\Database\Seeder;

class RaceSeeder extends Seeder
{
    /**
     * A tune-up race in the plan's third week and the target race at the end.
     */
    public function run(): void
    {
        $monday = now()->startOfWeek();

        Race::factory()->create(['date' => $monday->addWeeks(3)->addDays(6)]);
        Race::factory()->target()->create(['date' => $monday->addWeeks(5)->addDays(6)]);
    }
}

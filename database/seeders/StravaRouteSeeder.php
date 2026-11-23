<?php

namespace Database\Seeders;

use App\Models\StravaRoute;
use Illuminate\Database\Seeder;

class StravaRouteSeeder extends Seeder
{
    public function run(): void
    {
        StravaRoute::factory()->nighttime()->create(['name' => 'Town centre (lit)', 'distance_metres' => 5200]);
        StravaRoute::factory()->nighttime()->create(['name' => 'High street night loop', 'distance_metres' => 10100]);
        StravaRoute::factory()->create(['name' => 'Canal towpath', 'distance_metres' => 8000]);
        StravaRoute::factory()->create(['name' => 'Reservoir long run', 'distance_metres' => 21300, 'elevation_gain_metres' => 180]);
    }
}

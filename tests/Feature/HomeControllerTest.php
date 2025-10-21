<?php

use App\Enums\ActivityType;
use App\Models\Activity;
use Inertia\Testing\AssertableInertia as Assert;

it('renders Home with latest activity data', function () {
    $older = Activity::factory()
        ->run()
        ->state([
            'activity_id' => 111,
            'name' => 'Old Run',
            'distance' => 1000.0, // meters -> 1.00 km
            'moving_time' => 90, // 01:30
            'elapsed_time' => 90, // 01:30
            'total_elevation_gain' => 10.0, // meters
            'started_at' => now()->subDay(),
        ])->create();

    $newer = Activity::factory()
        ->run()
        ->state([
            'activity_id' => 222,
            'name' => 'New Run',
            'distance' => 5000.0, // meters -> 5.00 km
            'moving_time' => 3605, // 01:00:05
            'elapsed_time' => 4000, // 01:06:40
            'total_elevation_gain' => 25.5, // meters
            'started_at' => now(),
        ])->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('activity', fn (Assert $activity) => $activity
                ->where('activity_id', 222)
                ->where('name', 'New Run')
                ->where('type', ActivityType::Run->value)
                ->where('distance', 5.00)
                ->where('moving_time', '01:00:05')
                ->where('elapsed_time', '01:06:40')
                ->where('total_elevation_gain', 25.5)
                ->has('started_at')
                ->etc()
            )
        );
});

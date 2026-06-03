<?php

use App\Models\Activity;
use Inertia\Testing\AssertableInertia as Assert;

it('renders Home without activity data', function () {
    Activity::factory()
        ->run()
        ->state([
            'activity_id' => 222,
            'name' => 'New Run',
            'started_at' => now(),
        ])->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('New Run')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->missing('activity')
        );
});

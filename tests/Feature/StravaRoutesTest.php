<?php

use App\Mcp\Servers\TrainingServer;
use App\Mcp\Tools\ImportRoutesTool;
use App\Mcp\Tools\ListRoutesTool;
use App\Models\StravaRoute;
use App\Models\User;
use App\Training\RouteTime;
use Inertia\Testing\AssertableInertia as Assert;

it('imports routes through the MCP and marks night and lit routes as nighttime', function () {
    StravaRoute::factory()->create(['strava_id' => '1', 'name' => 'Deleted on Strava']);

    TrainingServer::actingAs(User::factory()->create())
        ->tool(ImportRoutesTool::class, ['routes' => [
            ['strava_id' => '3254184049876543210', 'name' => 'Canal loop', 'distance_metres' => 8123.4, 'elevation_gain_metres' => 35.6],
            ['strava_id' => '3254184049876543211', 'name' => 'Town centre (lit)', 'distance_metres' => 5012],
            ['strava_id' => '3254184049876543212', 'name' => 'High street', 'description' => 'Street lights all the way, good at night', 'distance_metres' => 10000],
            ['strava_id' => '3254184049876543213', 'name' => 'Unlit lanes', 'distance_metres' => 12000],
        ]])
        ->assertOk()
        ->assertSee('Saved 4 routes, 2 of them for night running.');

    expect(StravaRoute::query()->orderBy('strava_id')->get()->map->only(['strava_id', 'name', 'distance_metres', 'elevation_gain_metres', 'time'])->all())->toBe([
        ['strava_id' => '3254184049876543210', 'name' => 'Canal loop', 'distance_metres' => 8123, 'elevation_gain_metres' => 36, 'time' => RouteTime::Daytime],
        ['strava_id' => '3254184049876543211', 'name' => 'Town centre (lit)', 'distance_metres' => 5012, 'elevation_gain_metres' => 0, 'time' => RouteTime::Nighttime],
        ['strava_id' => '3254184049876543212', 'name' => 'High street', 'distance_metres' => 10000, 'elevation_gain_metres' => 0, 'time' => RouteTime::Nighttime],
        ['strava_id' => '3254184049876543213', 'name' => 'Unlit lanes', 'distance_metres' => 12000, 'elevation_gain_metres' => 0, 'time' => RouteTime::Daytime],
    ]);
});

it('keeps the routes page for the signed in owner', function () {
    $this->get(route('strava-routes.index'))->assertRedirect(route('login'));
});

it('shows nighttime and daytime routes, shortest first', function () {
    StravaRoute::factory()->nighttime()->create(['strava_id' => '10', 'name' => 'Lit 12k', 'distance_metres' => 12000]);
    StravaRoute::factory()->nighttime()->create(['strava_id' => '11', 'name' => 'Lit 6k', 'distance_metres' => 6000]);
    StravaRoute::factory()->create(['strava_id' => '13', 'name' => 'Day 8k', 'distance_metres' => 8000]);

    $this->actingAs(User::factory()->create())
        ->get(route('strava-routes.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Routes')
            ->where('nighttime.0.name', 'Lit 6k')
            ->where('nighttime.0.km', 6)
            ->where('nighttime.0.url', 'https://www.strava.com/routes/11')
            ->where('nighttime.1.name', 'Lit 12k')
            ->has('nighttime', 2)
            ->where('daytime.0.name', 'Day 8k')
            ->has('daytime', 1)
        );
});

it('lists nighttime routes within a distance range, shortest first', function () {
    StravaRoute::factory()->nighttime()->create(['strava_id' => '10', 'name' => 'Lit 12k', 'distance_metres' => 12000]);
    StravaRoute::factory()->nighttime()->create(['strava_id' => '11', 'name' => 'Lit 6k', 'distance_metres' => 6000]);
    StravaRoute::factory()->nighttime()->create(['strava_id' => '12', 'name' => 'Lit 20k', 'distance_metres' => 20000]);
    StravaRoute::factory()->create(['strava_id' => '13', 'name' => 'Day 8k', 'distance_metres' => 8000]);

    TrainingServer::actingAs(User::factory()->create())
        ->tool(ListRoutesTool::class, ['time' => 'nighttime', 'min_km' => 5, 'max_km' => 15])
        ->assertOk()
        ->assertSee([
            '{"name":"Lit 6k","km":6,"climb_metres":40,"time":"nighttime","url":"https://www.strava.com/routes/11"},{"name":"Lit 12k"',
            '"nighttime_count":2,"daytime_count":0',
        ])
        ->assertDontSee(['Lit 20k', 'Day 8k']);
});

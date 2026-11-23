<?php

use App\Mcp\Servers\TrainingServer;
use App\Mcp\Tools\ListRoutesTool;
use App\Models\StravaRoute;
use App\Models\StravaToken;
use App\Models\User;
use App\Training\RouteTime;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    StravaToken::factory()->create();
});

/**
 * @return array<string, mixed>
 */
function stravaRoute(string $id, string $name, float $distance, int $type = 2, ?string $description = null): array
{
    return [
        'id' => (int) $id,
        'id_str' => $id,
        'name' => $name,
        'description' => $description,
        'distance' => $distance,
        'elevation_gain' => 35.6,
        'type' => $type,
    ];
}

it('syncs running routes from the button and marks night and lit routes as nighttime', function () {
    StravaRoute::factory()->create(['strava_id' => '1', 'name' => 'Deleted on Strava']);

    Http::fake(['www.strava.com/api/v3/athletes/42/routes*' => Http::response([
        stravaRoute('3254184049876543210', 'Canal loop', 8123.4),
        stravaRoute('3254184049876543211', 'Town centre (lit)', 5012),
        stravaRoute('3254184049876543212', 'High street', 10000, description: 'Street lights all the way, good at night'),
        stravaRoute('3254184049876543213', 'Unlit lanes', 12000),
        stravaRoute('3254184049876543214', 'Bike ride', 40000, type: 1),
    ])]);

    $this->actingAs(User::factory()->create())
        ->from(route('strava-routes.index'))
        ->post(route('strava-routes.sync'))
        ->assertRedirect(route('strava-routes.index'))
        ->assertSessionHas('routesSynced', 4);

    expect(StravaRoute::query()->orderBy('strava_id')->get()->map->only(['strava_id', 'name', 'distance_metres', 'time'])->all())->toBe([
        ['strava_id' => '3254184049876543210', 'name' => 'Canal loop', 'distance_metres' => 8123, 'time' => RouteTime::Daytime],
        ['strava_id' => '3254184049876543211', 'name' => 'Town centre (lit)', 'distance_metres' => 5012, 'time' => RouteTime::Nighttime],
        ['strava_id' => '3254184049876543212', 'name' => 'High street', 'distance_metres' => 10000, 'time' => RouteTime::Nighttime],
        ['strava_id' => '3254184049876543213', 'name' => 'Unlit lanes', 'distance_metres' => 12000, 'time' => RouteTime::Daytime],
    ]);
});

it('fetches every page of routes', function () {
    $fullPage = array_map(fn (int $i): array => stravaRoute((string) $i, "Route {$i}", 5000), range(1, 200));

    Http::fake(['www.strava.com/api/v3/athletes/42/routes*' => Http::sequence()
        ->push($fullPage)
        ->push([stravaRoute('201', 'Route 201', 6000)])]);

    $this->actingAs(User::factory()->create())->post(route('strava-routes.sync'));

    expect(StravaRoute::query()->count())->toBe(201);
});

it('keeps routes and their sync button for the signed in owner', function () {
    Http::fake();

    $this->get(route('strava-routes.index'))->assertRedirect(route('login'));
    $this->post(route('strava-routes.sync'))->assertRedirect(route('login'));

    Http::assertNothingSent();
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

it('explains why the button could not sync routes', function () {
    $user = User::factory()->create();

    Http::fake(['www.strava.com/api/v3/athletes/42/routes*' => Http::response(['message' => 'Rate Limit Exceeded'], 429)]);

    $this->actingAs($user)
        ->post(route('strava-routes.sync'))
        ->assertSessionHasErrors(['routes' => 'Strava did not return your routes. Try again in a minute.']);

    StravaToken::query()->delete();

    $this->actingAs($user)
        ->post(route('strava-routes.sync'))
        ->assertSessionHasErrors(['routes' => 'Strava is not connected. Run strava:connect first.']);
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

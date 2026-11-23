<?php

use App\Jobs\SyncStravaActivity;
use App\Models\Activity;
use App\Models\StravaToken;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'services.strava.client_id' => '123',
        'services.strava.client_secret' => 'secret',
        'services.strava.verify_token' => 'verify-me',
    ]);
});

/**
 * @return array<string, mixed>
 */
function stravaRun(array $attributes = []): array
{
    return [
        'id' => 9001,
        'name' => 'Tempo run',
        'sport_type' => 'Run',
        'distance' => 7012.4,
        'moving_time' => 2520,
        'start_date' => '2026-11-22T07:30:00Z',
        ...$attributes,
    ];
}

it('answers the subscription check with the challenge', function () {
    $this->getJson('/api/strava/webhook?hub.mode=subscribe&hub.verify_token=verify-me&hub.challenge=abc123')
        ->assertSuccessful()
        ->assertExactJson(['hub.challenge' => 'abc123']);

    $this->getJson('/api/strava/webhook?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=abc123')
        ->assertForbidden();
});

it('queues a sync for an activity event from the connected athlete only', function () {
    Queue::fake();
    StravaToken::factory()->create();

    $this->postJson('/api/strava/webhook', [
        'object_type' => 'activity',
        'object_id' => 9001,
        'aspect_type' => 'create',
        'owner_id' => 42,
    ])->assertSuccessful();

    $this->postJson('/api/strava/webhook', [
        'object_type' => 'activity',
        'object_id' => 9002,
        'aspect_type' => 'create',
        'owner_id' => 7,
    ])->assertSuccessful();

    Queue::assertPushed(SyncStravaActivity::class, 1);
    Queue::assertPushed(SyncStravaActivity::class, fn (SyncStravaActivity $job): bool => $job->athleteId === 42 && $job->stravaId === 9001);
});

it('stores a new run and updates it when it changes on Strava', function () {
    StravaToken::factory()->create();

    Http::fake(['www.strava.com/api/v3/activities/9001' => Http::sequence()
        ->push(stravaRun())
        ->push(stravaRun(['name' => 'Tempo run in the rain']))]);

    (new SyncStravaActivity(42, 9001))->handle();

    $activity = Activity::query()->sole();

    expect($activity->only(['strava_id', 'name', 'distance_metres', 'moving_time_seconds']))->toBe([
        'strava_id' => 9001,
        'name' => 'Tempo run',
        'distance_metres' => 7012,
        'moving_time_seconds' => 2520,
    ])->and($activity->started_at->toDateTimeString())->toBe('2026-11-22 07:30:00');

    (new SyncStravaActivity(42, 9001))->handle();

    expect(Activity::query()->sole()->name)->toBe('Tempo run in the rain');

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer access'));
});

it('removes a run that was deleted or changed to another sport', function () {
    StravaToken::factory()->create();
    Activity::factory()->create(['strava_id' => 9001]);
    Activity::factory()->create(['strava_id' => 9002]);

    Http::fake([
        'www.strava.com/api/v3/activities/9001' => Http::response(['message' => 'Record Not Found'], 404),
        'www.strava.com/api/v3/activities/9002' => Http::response(stravaRun(['id' => 9002, 'sport_type' => 'Ride'])),
    ]);

    (new SyncStravaActivity(42, 9001))->handle();
    (new SyncStravaActivity(42, 9002))->handle();

    expect(Activity::query()->count())->toBe(0);
});

it('refreshes an expired access token and keeps the rotated refresh token', function () {
    StravaToken::factory()->expired()->create();

    Http::fake([
        'www.strava.com/oauth/token' => Http::response([
            'access_token' => 'new-access',
            'refresh_token' => 'new-refresh',
            'expires_at' => now()->addHours(6)->timestamp,
        ]),
        'www.strava.com/api/v3/activities/9001' => Http::response(stravaRun()),
    ]);

    (new SyncStravaActivity(42, 9001))->handle();

    $token = StravaToken::query()->sole();

    expect($token->access_token)->toBe('new-access')
        ->and($token->refresh_token)->toBe('new-refresh')
        ->and(Activity::query()->count())->toBe(1);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://www.strava.com/oauth/token'
        && $request['refresh_token'] === 'refresh');
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer new-access'));
});

it('connects a Strava athlete from the code Strava returns', function () {
    Http::fake(['www.strava.com/oauth/token' => Http::response([
        'access_token' => 'access',
        'refresh_token' => 'refresh',
        'expires_at' => now()->addHours(6)->timestamp,
        'athlete' => ['id' => 42],
    ])]);

    $this->artisan('strava:connect')
        ->expectsQuestion('Paste the address or its code', 'http://localhost/?state=&code=the-code&scope=read,activity:read_all')
        ->assertSuccessful();

    expect(StravaToken::query()->sole()->athlete_id)->toBe(42);

    Http::assertSent(fn (Request $request): bool => $request['code'] === 'the-code'
        && $request['grant_type'] === 'authorization_code');
});

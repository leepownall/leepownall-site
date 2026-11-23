<?php

use App\Jobs\ImportStravaActivities;
use App\Models\Activity;
use App\Models\RunPace;
use App\Models\ScheduledSession;
use App\Training\MarathonPlan;
use App\Training\RunDuration;
use App\Training\RunEffort;
use App\Training\WeekKind;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    MarathonPlan::replace(sampleTrainingPlan());
});

it('imports runs and stores average paces', function () {
    $this->travelTo('2026-10-06');

    $path = activitiesExport(<<<'CSV'
Activity ID,Activity Date,Activity Name,Activity Type,Moving Time,Distance
1,"Sep 1, 2026, 7:00:00 AM",Easy run,Run,2400,6000
2,"Sep 2, 2026, 7:00:00 AM",Tempo run,Run,900,3000
3,"Sep 3, 2026, 7:00:00 AM",Morning jog,Run,1550,5000
4,"Sep 4, 2026, 7:00:00 AM",Sunday long run,Run,4000,10000
5,"Sep 5, 2026, 7:00:00 AM",Lunch ride,Ride,3600,20000
6,"Jan 1, 2026, 7:00:00 AM",Easy run,Run,6000,6000
CSV);

    (new ImportStravaActivities($path))->handle();

    expect(Activity::query()->count())->toBe(5)
        ->and(Activity::query()->where('name', 'Lunch ride')->exists())->toBeFalse()
        ->and(RunPace::query()->where('effort', RunEffort::Easy)->value('seconds_per_kilometre'))->toBe(370)
        ->and(RunPace::query()->where('effort', RunEffort::Tempo)->value('seconds_per_kilometre'))->toBe(300)
        ->and(ScheduledSession::query()->whereDate('date', '2027-01-11')->value('estimated_minutes'))->toBe(37);
});

it('uses a configurable window of recent runs', function () {
    $this->travelTo('2026-10-06');

    Activity::factory()->create([
        'started_at' => '2026-09-01',
        'distance_metres' => 6000,
        'moving_time_seconds' => 2400,
    ]);

    Activity::factory()->create([
        'started_at' => '2026-01-01',
        'distance_metres' => 6000,
        'moving_time_seconds' => 6000,
    ]);

    expect(RunPace::query()->where('effort', RunEffort::Easy)->value('seconds_per_kilometre'))->toBe(400);

    config(['training.pace_months' => 12]);

    Activity::query()->first()->touch();

    expect(RunPace::query()->where('effort', RunEffort::Easy)->value('seconds_per_kilometre'))->toBe(700);
});

it('recalculates scheduled runs when an activity changes', function () {
    $activity = Activity::factory()->create([
        'distance_metres' => 6000,
        'moving_time_seconds' => 2400,
    ]);

    expect(ScheduledSession::query()->whereDate('date', '2027-01-11')->value('estimated_minutes'))->toBe(40)
        ->and(ScheduledSession::query()->whereDate('date', '2026-10-07')->value('estimated_minutes'))->toBe(45);

    $activity->update([
        'moving_time_seconds' => 3600,
    ]);

    expect(ScheduledSession::query()->whereDate('date', '2027-01-11')->value('estimated_minutes'))->toBe(60)
        ->and(ScheduledSession::query()->whereDate('date', '2026-10-07')->value('estimated_minutes'))->toBe(59);

    $activity->delete();

    expect(ScheduledSession::query()->whereDate('date', '2027-01-11')->value('estimated_minutes'))->toBeNull();
});

it('queues an import from an export directory', function () {
    Queue::fake();

    $directory = sys_get_temp_dir().'/strava-export-'.uniqid();
    mkdir($directory);
    file_put_contents($directory.'/activities.csv', "Activity ID,Activity Date,Activity Name,Activity Type,Moving Time,Distance\n");

    $this->artisan('training:import-strava', ['path' => $directory])
        ->assertSuccessful();

    Queue::assertPushed(ImportStravaActivities::class, function (ImportStravaActivities $job) use ($directory): bool {
        return $job->path === $directory.'/activities.csv';
    });
});

it('does not queue an import when the export is missing', function () {
    Queue::fake();

    $this->artisan('training:import-strava', ['path' => '/tmp/missing-activities.csv'])
        ->assertFailed();

    Queue::assertNothingPushed();
});

it('estimates forty minutes for a six kilometre easy run', function () {
    $minutes = RunDuration::minutes('Easy run', ['6 km'], [
        RunEffort::Easy->value => 400,
    ]);

    expect($minutes)->toBe(40);
});

it('adds each segment of a mixed run', function () {
    $minutes = RunDuration::minutes('Progression', [
        '7 km',
        '2 km easy',
        '3 km tempo',
        '2 km easy',
    ], [
        RunEffort::Easy->value => 400,
        RunEffort::Tempo->value => 300,
    ]);

    expect($minutes)->toBe(42);
});

it('stores marathon and tempo paces when recent runs do not include them', function () {
    $this->travelTo('2026-10-06');

    Activity::factory()->create([
        'name' => 'Morning Run',
        'started_at' => '2026-09-01',
        'distance_metres' => 6000,
        'moving_time_seconds' => 2346,
    ]);

    expect(RunPace::query()->where('effort', RunEffort::Easy)->value('seconds_per_kilometre'))->toBe(391)
        ->and(RunPace::query()->where('effort', RunEffort::MarathonPace)->value('seconds_per_kilometre'))->toBe(384)
        ->and(RunPace::query()->where('effort', RunEffort::Tempo)->value('seconds_per_kilometre'))->toBe(360)
        ->and(ScheduledSession::query()->whereDate('date', '2026-10-10')->value('estimated_minutes'))->toBe(104);
});

it('prefers a recorded effort over the stored target pace', function () {
    Activity::factory()->create([
        'name' => 'Tempo run',
        'distance_metres' => 3000,
        'moving_time_seconds' => 900,
    ]);

    expect(RunPace::query()->where('effort', RunEffort::Tempo)->value('seconds_per_kilometre'))->toBe(300)
        ->and(RunPace::query()->where('effort', RunEffort::MarathonPace)->value('seconds_per_kilometre'))->toBe(384);
});

it('splits a mixed run into each pace', function () {
    $estimate = RunDuration::estimate('Long run', [
        '26 km',
        '6 km easy',
        '4 km marathon pace',
        '4 km easy',
        '3 km marathon pace',
        '3 km easy',
        '3 km marathon pace',
        '3 km easy',
    ], [
        RunEffort::Easy->value => 391,
        RunEffort::MarathonPace->value => 384,
    ]);

    expect($estimate['fixedSeconds'])->toBe(0)
        ->and($estimate['efforts'])->toBe([
            ['effort' => 'easy', 'metres' => 16000, 'pace' => 391],
            ['effort' => 'marathon pace', 'metres' => 10000, 'pace' => 384],
        ]);
});

it('keeps written minutes separate from paced distance', function () {
    $estimate = RunDuration::estimate('Speed session', [
        '10 minutes easy',
        '5 × 800 m interval pace',
        '2 minutes walk',
    ], [
        RunEffort::Easy->value => 400,
        RunEffort::IntervalPace->value => 300,
    ]);

    expect($estimate['fixedSeconds'])->toBe(720)
        ->and($estimate['efforts'])->toBe([
            ['effort' => 'interval pace', 'metres' => 4000, 'pace' => 300],
        ]);
});

it('uses the easy pace when a faster effort has no average yet', function () {
    $minutes = RunDuration::minutes('Progression', [
        '7 km',
        '2 km easy',
        '2 km marathon pace',
        '1 km tempo',
        '2 km easy',
    ], [
        RunEffort::Easy->value => 400,
    ]);

    expect($minutes)->toBe(47);
});

it('uses the minutes already written into a speed session', function () {
    $page = MarathonPlan::present(WeekKind::Prep, 2, now(), includePrivate: true);

    expect($page['days'][2]['session']['name'])->toBe('Speed session')
        ->and($page['days'][2]['estimatedMinutes'])->toBe(37);
});

function activitiesExport(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'strava');
    file_put_contents($path, $contents);

    return $path;
}

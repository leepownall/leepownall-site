<?php

use App\Models\Activity;
use App\Models\Race;
use App\Models\RunPace;
use App\Models\TrainingWeek;
use App\Models\User;
use App\Training\MarathonPlan;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    MarathonPlan::replace(sampleTrainingPlan());
});

it('shows the current build-up week before the plan starts', function () {
    $this->travelTo('2026-10-06');

    $this->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Training')
            ->where('kind', 'prep')
            ->where('week', 1)
            ->where('caption', 'build-up')
            ->where('phase', 'Build-up')
            ->where('range', '5–11 Oct 2026')
            ->where('current', 'prep-1')
            ->where('previous', null)
            ->where('next.kind', 'prep')
            ->where('next.week', 2)
            ->has('days', 7)
            ->has('weeks', 4)
            ->where('weeks.0.kind', 'prep')
            ->where('weeks.0.week', 1)
            ->where('weeks.0.phase', 'Build-up')
            ->where('weeks.0.km', 29)
            ->where('weeks.3.id', 'plan-2')
            ->where('weeks.3.km', 31)
            ->where('days.0.label', 'Monday 5 Oct')
            ->where('days.0.isToday', false)
            ->where('days.0.session.name', 'Easy run')
            ->where('days.0.session.lines', ['6 km'])
            ->where('days.1.label', 'Tuesday 6 Oct')
            ->where('days.1.isToday', true)
            ->where('days.1.isRest', true)
            ->where('days.2.session.name', 'Progression')
            ->where('days.3.session', null)
            ->where('days.3.strength', 30)
            ->where('days.5.session.lines.0', '16 km')
            ->where('days.0.estimatedMinutes', null)
        );
});

it('stays on the last build-up week until the plan starts', function () {
    $this->travelTo('2026-12-01');

    $this->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('current', 'prep-2')
            ->where('next.kind', 'plan')
            ->where('next.week', 1)
        );
});

it('shows a plan week with the build-up week before it', function () {
    $this->travelTo('2026-10-06');

    $this->get(route('training', ['week' => 1]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('kind', 'plan')
            ->where('week', 1)
            ->where('caption', 'of 2')
            ->where('phase', 'Prep')
            ->where('range', '11–17 Jan 2027')
            ->where('previous.kind', 'prep')
            ->where('previous.week', 2)
            ->where('next.kind', 'plan')
            ->where('next.week', 2)
            ->where('days.5.session.lines', ['13 km easy'])
        );

    $this->get(route('training.prep', ['prep' => 2]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('kind', 'prep')
            ->where('week', 2)
            ->where('range', '12–18 Oct 2026')
            ->where('days.2.session.name', 'Speed session')
        );
});

it('includes a leave by estimate for a run and omits it on a rest day', function () {
    Activity::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('training', ['week' => 1]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.0.session.name', 'Easy run')
            ->where('days.0.estimatedMinutes', 40)
            ->where('days.0.estimatedPace', 400)
            ->where('days.0.estimatedMetres', 6000)
            ->where('days.0.fixedSeconds', 0)
            ->where('days.0.efforts', [[
                'effort' => 'easy',
                'metres' => 6000,
                'pace' => 400,
            ]])
            ->where('days.2.efforts', [
                ['effort' => 'easy', 'metres' => 4000, 'pace' => 400],
                ['effort' => 'tempo', 'metres' => 3000, 'pace' => 360],
            ])
            ->where('days.0.similarRuns', [[
                'name' => 'Easy run',
                'minutes' => 40,
                'pace' => 400,
                'date' => now()->format('j M'),
            ]])
            ->where('days.1.isRest', true)
            ->where('days.1.estimatedMinutes', null)
            ->where('days.1.estimatedPace', null)
            ->where('days.1.estimatedMetres', null)
            ->where('days.1.fixedSeconds', 0)
            ->where('days.1.efforts', [])
            ->where('days.1.similarRuns', [])
        );
});

it('keeps a run and a strength session on the same day', function () {
    $this->travelTo('2027-01-24');

    $this->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('week', 2)
            ->where('days.0.session.name', 'Easy run')
            ->where('days.0.session.lines', ['5 km'])
            ->where('days.0.strength', 30)
            ->where('days.2.strength', null)
            ->where('days.6.isToday', true)
            ->where('days.6.session.name', 'Stabridge Stagger')
        );
});

it('shows the last week after the plan has finished', function () {
    $this->travelTo('2027-04-26');

    $this->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('kind', 'plan')
            ->where('week', 2)
            ->where('phase', 'Race')
            ->where('next', null)
        );
});

it('does not show a week outside the plan', function () {
    $this->get('/training/3')->assertNotFound();
    $this->get('/training/0')->assertNotFound();
    $this->get('/training/prep/0')->assertNotFound();
    $this->get('/training/prep/3')->assertNotFound();
});

it('says there is no plan yet before one is imported', function () {
    TrainingWeek::query()->delete();

    $this->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('TrainingEmpty'));

    $this->get(route('training', ['week' => 1]))->assertNotFound();
});

it('replaces the stored plan and numbers weeks by date within their kind', function () {
    [$prepOne, $prepTwo, $planOne, $planTwo] = sampleTrainingPlan();
    $planTwo['phase'] = 'Taper';

    MarathonPlan::replace([$planTwo, $prepTwo, $planOne]);

    expect(MarathonPlan::prepLength())->toBe(1)
        ->and(MarathonPlan::planLength())->toBe(2)
        ->and(TrainingWeek::query()->orderBy('starts_on')->get(['kind', 'number', 'phase'])->toArray())->toBe([
            ['kind' => 'prep', 'number' => 1, 'phase' => 'Build-up'],
            ['kind' => 'plan', 'number' => 1, 'phase' => 'Prep'],
            ['kind' => 'plan', 'number' => 2, 'phase' => 'Taper'],
        ]);

    $this->get(route('training.prep', ['prep' => 1]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('range', '12–18 Oct 2026')
            ->where('days.2.session.name', 'Speed session')
        );
});

it('compares what was run with the plan for each day', function () {
    $this->travelTo('2026-10-08 12:00');

    Activity::factory()->create([
        'strava_id' => 501,
        'name' => 'Midnight miles',
        'started_at' => '2026-10-04 23:30:00',
        'distance_metres' => 6400,
        'moving_time_seconds' => 2560,
    ]);
    Activity::factory()->create([
        'strava_id' => 502,
        'name' => 'Shakeout',
        'started_at' => '2026-10-06 06:00:00',
        'distance_metres' => 3000,
        'moving_time_seconds' => 1200,
    ]);
    Activity::factory()->create(['strava_id' => 503, 'started_at' => '2026-10-12 06:00:00']);

    $this->actingAs(User::factory()->create())
        ->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('current', 'prep-1')
            ->where('days.0.status', 'done')
            ->where('days.0.actual', [[
                'name' => 'Midnight miles',
                'metres' => 6400,
                'minutes' => 43,
                'pace' => 400,
                'url' => 'https://www.strava.com/activities/501',
            ]])
            ->where('days.1.status', 'extra')
            ->where('days.1.actual.0.name', 'Shakeout')
            ->where('days.2.status', 'missed')
            ->where('days.2.actual', [])
            ->where('days.3.status', null)
            ->where('days.5.status', null)
            ->where('days.6.actual', [])
        );
});

it('shows guests the plan without runs, paces, or leave by estimates', function () {
    $this->travelTo('2026-10-08 12:00');

    Activity::factory()->create(['name' => 'Private morning run', 'started_at' => '2026-10-05 06:00:00']);

    $this->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.0.session.name', 'Easy run')
            ->where('days.0.session.lines', ['6 km'])
            ->where('days.0.estimatedMinutes', null)
            ->where('days.0.estimatedPace', null)
            ->where('days.0.efforts', [])
            ->where('days.0.similarRuns', [])
            ->where('days.0.actual', [])
            ->where('days.0.status', null)
            ->where('days.2.status', null)
        )
        ->assertDontSee('Private morning run');
});

it('shows races on their day and counts down to the target race', function () {
    $this->travelTo('2027-01-13');

    Race::factory()->create();
    Race::factory()->target()->create(['date' => '2027-01-31']);

    $this->get(route('training', ['week' => 2]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.5.races', [])
            ->where('days.6.races', [['name' => 'Stabridge Stagger', 'km' => 16.1, 'kind' => 'race']])
            ->where('targetRace', [
                'name' => 'Manchester Marathon',
                'date' => '31 Jan 2027',
                'km' => 42.2,
                'daysToGo' => 18,
            ])
            ->where('weeks.0.race', null)
            ->where('weeks.2.race', null)
            ->where('weeks.3.race', 'race')
        );
});

it('has no target race until one is set', function () {
    $this->get(route('training', ['week' => 1]))
        ->assertInertia(fn (Assert $page) => $page->where('targetRace', null)->where('days.6.races', []));
});

it('takes today from the owner\'s timezone, not UTC', function () {
    $this->travelTo('2026-10-11 23:30:00');

    $this->get(route('training'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('current', 'prep-2')
            ->where('days.0.date', '2026-10-12')
            ->where('days.0.isToday', true)
        );
});

it('shows every race on a day and counts the days since a past target race', function () {
    $this->travelTo('2027-01-26');

    Race::factory()->create(['name' => 'Morning 5k', 'distance_metres' => 5000]);
    Race::factory()->target()->create(['name' => 'Afternoon 10 mile', 'date' => '2027-01-24', 'distance_metres' => 16093]);

    $this->get(route('training', ['week' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.6.races', [
                ['name' => 'Morning 5k', 'km' => 5, 'kind' => 'race'],
                ['name' => 'Afternoon 10 mile', 'km' => 16.1, 'kind' => 'target'],
            ])
            ->where('targetRace.daysToGo', -2)
            ->where('weeks.3.race', 'target')
        );
});

it('does not recalculate paces when a guest views the plan', function () {
    Activity::factory()->create();
    RunPace::query()->delete();

    $this->get(route('training', ['week' => 1]))->assertSuccessful();

    expect(RunPace::query()->count())->toBe(0);
});

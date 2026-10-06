<?php

use App\Training\MarathonPlan;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the current build-up week before the plan starts', function () {
    $this->travelTo('2026-10-06');

    $this->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Training')
            ->where('kind', 'prep')
            ->where('week', 2)
            ->where('caption', 'build-up')
            ->where('phase', 'Build-up')
            ->where('range', '5–11 Oct 2026')
            ->where('current', 'prep-1')
            ->where('previous', null)
            ->where('next.kind', 'prep')
            ->where('next.week', 2)
            ->has('days', 7)
            ->has('weeks', MarathonPlan::prepLength() + MarathonPlan::LENGTH)
            ->where('weeks.0.kind', 'prep')
            ->where('weeks.0.label', 2)
            ->where('weeks.0.phase', 'Build-up')
            ->where('weeks.0.km', 29)
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
            ->where('days.5.session.name', 'Long run')
            ->where('days.5.session.lines.0', '16 km')
        )
        ->assertDontSee('Circuit')
        ->assertDontSee('Parkes')
        ->assertDontSee('warm up');

    expect(MarathonPlan::prepLength())->toBe(14);
});

it('keeps the official plan available before it starts', function () {
    $this->travelTo('2026-10-06');

    $this->get(route('training', ['week' => 1]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('kind', 'plan')
            ->where('week', 1)
            ->where('caption', 'of 15')
            ->where('phase', 'Prep')
            ->where('range', '11–17 Jan 2027')
            ->where('previous.kind', 'prep')
            ->where('previous.week', MarathonPlan::prepLength())
            ->where('days.0.label', 'Monday 11 Jan')
            ->where('days.5.session.lines', ['13 km easy'])
        );
});

it('eases the week before the plan starts', function () {
    $this->travelTo('2027-01-04');

    $this->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('kind', 'prep')
            ->where('week', 1)
            ->where('range', '4–10 Jan 2027')
            ->where('days.2.session.name', 'Tempo run')
            ->where('days.5.session.name', 'Long run')
            ->where('days.5.session.lines', ['13 km easy'])
            ->where('next.kind', 'plan')
            ->where('next.week', 1)
        );
});

it('marks today and shows that days run', function () {
    $this->travelTo('2027-01-13');

    $this->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Training')
            ->where('kind', 'plan')
            ->where('week', 1)
            ->where('days.2.label', 'Wednesday 13 Jan')
            ->where('days.2.isToday', true)
            ->where('days.2.session.name', 'Tempo run')
            ->where('days.2.session.lines', ['7 km', '2 km easy', '3 km tempo', '2 km easy'])
            ->where('days.0.isToday', false)
        );
});

it('shows a later week and the following week', function () {
    $this->get(route('training', ['week' => 2]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Training')
            ->where('kind', 'plan')
            ->where('week', 2)
            ->where('phase', 'Prep')
            ->where('previous.kind', 'plan')
            ->where('previous.week', 1)
            ->where('next.kind', 'plan')
            ->where('next.week', 3)
            ->where('days.2.session.name', 'Progression')
            ->where('days.2.session.lines', ['7 km', '2 km easy', '2 km marathon pace', '1 km tempo', '2 km easy'])
        );
});

it('puts the ten mile race on the sunday of week four', function () {
    $this->travelTo('2027-02-07');

    $this->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('kind', 'plan')
            ->where('week', 4)
            ->where('phase', 'Recovery')
            ->where('range', '1–7 Feb 2027')
            ->where('days.5.session.name', 'Easy run')
            ->where('days.5.session.lines', ['5 km'])
            ->where('days.6.date', '2027-02-07')
            ->where('days.6.isToday', true)
            ->where('days.6.session.name', 'Stabridge Stagger')
            ->where('days.6.session.lines', ['16 km'])
            ->where('weeks.17.km', 32)
        );
});

it('shows the race week after the plan has finished', function () {
    $this->travelTo('2027-04-26');

    $this->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Training')
            ->where('week', 15)
            ->where('phase', 'Race')
            ->where('next', null)
            ->where('days.6.session.name', 'Race day')
            ->where('days.6.isToday', false)
        );
});

it('keeps a run and a strength session on the same day', function () {
    $this->get(route('training', ['week' => 7]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.0.session.name', 'Easy run')
            ->where('days.0.session.lines', ['5 km'])
            ->where('days.0.strength', 30)
            ->where('days.3.session.name', 'Easy run and strides')
            ->where('days.3.strength', null)
        )
        ->assertDontSee('Circuit');
});

it('does not show a week outside the plan', function () {
    $this->get('/training/16')->assertNotFound();
    $this->get('/training/0')->assertNotFound();
    $this->get('/training/prep/0')->assertNotFound();
    $this->get('/training/prep/99')->assertNotFound();
});

it('includes a day for every day of every week', function () {
    foreach (range(1, MarathonPlan::prepLength()) as $prep) {
        expect(MarathonPlan::presentPrep($prep, now())['days'])->toHaveCount(7);
    }

    foreach (range(1, MarathonPlan::LENGTH) as $week) {
        $page = MarathonPlan::present($week, now());

        expect($page['days'])->toHaveCount(7)
            ->and($page['week'])->toBe($week);
    }
});

<?php

use App\Mcp\Servers\TrainingServer;
use App\Mcp\Tools\GetTrainingPlanTool;
use App\Mcp\Tools\ImportTrainingPlanTool;
use App\Mcp\Tools\SetRacesTool;
use App\Models\Race;
use App\Models\TrainingWeek;
use App\Models\User;
use App\Training\MarathonPlan;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passport\Passport;

it('imports a plan that the training page then shows', function () {
    $this->travelTo('2026-10-06');

    TrainingServer::actingAs(User::factory()->create())
        ->tool(ImportTrainingPlanTool::class, ['weeks' => sampleTrainingPlan()])
        ->assertOk()
        ->assertSee('Imported 2 build-up weeks and 2 plan weeks.');

    $this->get(route('training'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('current', 'prep-1')
            ->where('days.2.session.name', 'Progression')
            ->has('weeks', 4)
        );
});

it('rejects a week that does not start on a Monday or has the wrong number of days', function () {
    MarathonPlan::replace(sampleTrainingPlan());

    $weeks = sampleTrainingPlan();
    $weeks[0]['starts_on'] = '2026-10-06';
    $weeks[1]['days'] = array_slice($weeks[1]['days'], 0, 6);

    TrainingServer::actingAs(User::factory()->create())
        ->tool(ImportTrainingPlanTool::class, ['weeks' => $weeks])
        ->assertHasErrors([
            '2026-10-06 is not a Monday.',
            'Each week needs exactly seven days, Monday first.',
        ]);

    expect(TrainingWeek::query()->count())->toBe(4);
});

it('returns the stored plan', function () {
    MarathonPlan::replace(sampleTrainingPlan());

    TrainingServer::actingAs(User::factory()->create())
        ->tool(GetTrainingPlanTool::class)
        ->assertOk()
        ->assertSee([
            '"starts_on":"2027-01-18","kind":"plan","number":2,"phase":"Race"',
            '"date":"2027-01-24","name":"Stabridge Stagger","lines":["16 km"]',
        ]);
});

it('requires an OAuth token to reach the MCP server', function () {
    $request = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];

    $this->postJson('/mcp/training', $request)->assertUnauthorized();

    Passport::actingAs(User::factory()->create(), ['mcp:use']);

    $this->postJson('/mcp/training', $request)
        ->assertSuccessful()
        ->assertJsonPath('result.tools.*.name', ['import-training-plan', 'get-training-plan', 'list-routes', 'set-races']);
});

it('replaces the races with exactly one target', function () {
    Race::factory()->create(['name' => 'Old race']);

    TrainingServer::actingAs(User::factory()->create())
        ->tool(SetRacesTool::class, ['races' => [
            ['name' => 'Stabridge Stagger', 'date' => '2027-02-07', 'distance_km' => 16.09],
            ['name' => 'Manchester Marathon', 'date' => '2027-04-25', 'distance_km' => 42.195, 'target' => true],
        ]])
        ->assertOk()
        ->assertSee('Saved 2 races. The target is Manchester Marathon on 2027-04-25.');

    expect(Race::query()->orderBy('date')->get()->map->only(['name', 'distance_metres', 'is_target'])->all())->toBe([
        ['name' => 'Stabridge Stagger', 'distance_metres' => 16090, 'is_target' => false],
        ['name' => 'Manchester Marathon', 'distance_metres' => 42195, 'is_target' => true],
    ]);

    TrainingServer::actingAs(User::factory()->create())
        ->tool(SetRacesTool::class, ['races' => [
            ['name' => 'A', 'date' => '2027-02-07', 'distance_km' => 10, 'target' => true],
            ['name' => 'B', 'date' => '2027-04-25', 'distance_km' => 42.2, 'target' => true],
        ]])
        ->assertHasErrors(['Mark exactly one race as the target.']);

    expect(Race::query()->count())->toBe(2);
});

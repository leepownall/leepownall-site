<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Two build-up weeks and two plan weeks, in the shape the MCP import takes.
 *
 * @return list<array<string, mixed>>
 */
function sampleTrainingPlan(): array
{
    $run = fn (string $name, string ...$lines): array => ['name' => $name, 'lines' => $lines];
    $rest = ['rest' => true];
    $strength = ['strength' => true];

    return [
        [
            'starts_on' => '2026-10-05',
            'kind' => 'prep',
            'phase' => 'Build-up',
            'days' => [
                $run('Easy run', '6 km'),
                $rest,
                $run('Progression', '7 km', '2 km easy', '2 km marathon pace', '1 km tempo', '2 km easy'),
                $strength,
                $rest,
                $run('Long run', '16 km', '6 km easy', '5 km marathon pace', '5 km easy'),
                $rest,
            ],
        ],
        [
            'starts_on' => '2026-10-12',
            'kind' => 'prep',
            'phase' => 'Build-up',
            'days' => [
                $run('Easy run and strides', '6 km', '4 × 20s strides'),
                $rest,
                $run('Speed session', '10 minutes easy', '5 × 3 minutes interval pace', '2 minutes walk', '10 minutes easy'),
                $strength,
                $rest,
                $run('Long run', '19 km easy'),
                $rest,
            ],
        ],
        [
            'starts_on' => '2027-01-11',
            'kind' => 'plan',
            'phase' => 'Prep',
            'days' => [
                $run('Easy run', '6 km'),
                $rest,
                $run('Tempo run', '7 km', '2 km easy', '3 km tempo', '2 km easy'),
                $strength,
                $rest,
                $run('Long run', '13 km easy'),
                $rest,
            ],
        ],
        [
            'starts_on' => '2027-01-18',
            'kind' => 'plan',
            'phase' => 'Race',
            'days' => [
                ['name' => 'Easy run', 'lines' => ['5 km'], 'strength' => true],
                $rest,
                $run('Easy run and strides', '5 km', '4 × 20s strides'),
                $rest,
                $run('Easy run', '5 km'),
                $rest,
                $run('Stabridge Stagger', '16 km'),
            ],
        ],
    ];
}

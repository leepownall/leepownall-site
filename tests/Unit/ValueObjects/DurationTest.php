<?php

use App\ValueObjects\Duration;

it('creates a duration with null value', function () {
    $duration = new Duration;

    expect($duration->value)->toBe(0);
});

it('creates a duration with valid value', function () {
    $duration = new Duration(3600);

    expect($duration->value)->toBe(3600);
});

it('converts seconds to readable format correctly', function (int $seconds, string $expectedReadable) {
    $duration = new Duration($seconds);

    expect($duration->asReadable())->toBe($expectedReadable);
})->with([
    'zero seconds' => [0, '00:00'],
    'one minute' => [60, '01:00'],
    'one minute thirty seconds' => [90, '01:30'],
    'one hour' => [3600, '01:00:00'],
    'one hour one minute' => [3660, '01:01:00'],
    'one hour one minute thirty seconds' => [3690, '01:01:30'],
    'two hours' => [7200, '02:00:00'],
    'two hours thirty minutes' => [9000, '02:30:00'],
    'two hours thirty minutes forty five seconds' => [9045, '02:30:45'],
    'large duration' => [3661, '01:01:01'],
    'very large duration' => [86400, '24:00:00'], // 24 hours
    'negative duration' => [-3600, '00:00'],
]);

it('returns correct cast class', function () {
    expect(Duration::castUsing([]))->toBe(\App\Casts\DurationCast::class);
});

it('resolves displayable value correctly', function (int $seconds, float|string $expectedValue) {
    $duration = new Duration($seconds);

    expect($duration->resolveDisplayableValue())->toBe($expectedValue);
})->with([
    'zero duration' => [0, '-'],
    'one minute' => [60, '01:00'],
    'one hour' => [3600, '01:00:00'],
    'two hours thirty minutes' => [9000, '02:30:00'],
    'negative duration' => [-3600, '00:00'],
]);

<?php

use App\ValueObjects\Distance;

it('creates a distance with null value', function () {
    $distance = new Distance;

    expect($distance->value)->toBe(0.0);
});

it('creates a distance with valid value', function () {
    $distance = new Distance(1000.0);

    expect($distance->value)->toBe(1000.0);
});

it('converts meters to miles correctly', function (float $meters, float $expectedMiles) {
    $distance = new Distance($meters);

    expect($distance->asMiles())->toBe($expectedMiles);
})->with([
    'exact mile' => [1609.34, 1.0],
    'mile with extra precision' => [1609.344, 1.0],
    'half mile' => [804.67, 0.5],
    'two miles' => [3218.68, 2.0],
    'very small distance' => [0.1, 0.0],
    'large distance' => [1000000.0, 621.0],
    'negative distance' => [-1000.0, -0.62],
]);

it('converts meters to kilometers correctly', function (float $meters, float $expectedKilometers) {
    $distance = new Distance($meters);

    expect($distance->asKilometers())->toBe($expectedKilometers);
})->with([
    'exact kilometer' => [1000.0, 1.0],
    'kilometer with extra precision' => [1000.123, 1.0],
    'half kilometer' => [500.0, 0.5],
    'two kilometers' => [2000.0, 2.0],
    'very small distance' => [0.1, 0.0],
    'large distance' => [1000000.0, 1000.0],
    'negative distance' => [-1000.0, -1.0],
]);

it('returns correct cast class', function () {
    expect(Distance::castUsing([]))->toBe(\App\Casts\DistanceCast::class);
});

it('converts to string correctly', function (float $meters, string $expectedString) {
    $distance = new Distance($meters);

    expect((string) $distance)->toBe($expectedString);
})->with([
    'positive value' => [1500.5, '1500.5'],
    'zero value' => [0.0, '0'],
    'negative value' => [-500.25, '-500.25'],
    'integer value' => [1000.0, '1000'],
]);

it('resolves displayable value correctly', function (float $meters, float|string $expectedValue) {
    $distance = new Distance($meters);

    expect($distance->resolveDisplayableValue())->toBe($expectedValue);
})->with([
    'zero distance' => [0.0, '-'],
    'one kilometer' => [1000.0, 1.0],
    'one and half kilometers' => [1500.0, 1.5],
    'very small distance' => [0.1, 0.0],
    'large distance' => [1000000.0, 1000.0],
    'negative distance' => [-1000.0, -1.0],
]);

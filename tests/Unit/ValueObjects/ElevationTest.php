<?php

use App\ValueObjects\Elevation;

it('creates an elevation with null value', function () {
    $elevation = new Elevation;

    expect($elevation->value)->toBe(0.0);
});

it('creates an elevation with valid value', function () {
    $elevation = new Elevation(1000.0);

    expect($elevation->value)->toBe(1000.0);
});

it('converts meters to feet correctly', function (float $meters, float $expectedFeet) {
    $elevation = new Elevation($meters);

    expect($elevation->asFeet())->toBe($expectedFeet);
})->with([
    'zero elevation' => [0.0, 0.0],
    'one meter' => [1.0, 3.28],
    'ten meters' => [10.0, 32.8],
    'one hundred meters' => [100.0, 328.0],
    'one thousand meters' => [1000.0, 3280.0],
    'negative elevation' => [-100.0, -328.0],
    'small elevation' => [0.1, 0.33],
    'large elevation' => [8848.0, 29021.44], // Mount Everest
]);

it('converts meters to feet with custom precision', function (float $meters, int $precision, float $expectedFeet) {
    $elevation = new Elevation($meters);

    expect($elevation->asFeet($precision))->toBe($expectedFeet);
})->with([
    'one meter with 1 decimal' => [1.0, 1, 3.3],
    'one meter with 3 decimals' => [1.0, 3, 3.280],
    'ten meters with 0 decimals' => [10.0, 0, 33.0],
    'small elevation with 4 decimals' => [0.1, 4, 0.328],
]);

it('converts to meters with custom precision', function (float $meters, int $precision, float $expectedMeters) {
    $elevation = new Elevation($meters);

    expect($elevation->asMeters($precision))->toBe($expectedMeters);
})->with([
    'one meter with 1 decimal' => [1.0, 1, 1.0],
    'one meter with 3 decimals' => [1.0, 3, 1.000],
    'small elevation with 4 decimals' => [0.123456, 4, 0.1235],
    'negative elevation with 2 decimals' => [-100.123456, 2, -100.12],
]);

it('checks if elevation is blank correctly', function (float $meters, bool $expectedBlank) {
    $elevation = new Elevation($meters);

    expect($elevation->isBlank())->toBe($expectedBlank);
})->with([
    'zero elevation' => [0.0, true],
    'positive elevation' => [100.0, false],
    'negative elevation' => [-100.0, false],
    'very small elevation' => [0.001, false],
]);

it('checks if elevation is not blank correctly', function (float $meters, bool $expectedNotBlank) {
    $elevation = new Elevation($meters);

    expect($elevation->isNotBlank())->toBe($expectedNotBlank);
})->with([
    'zero elevation' => [0.0, false],
    'positive elevation' => [100.0, true],
    'negative elevation' => [-100.0, true],
    'very small elevation' => [0.001, true],
]);

it('converts to string correctly', function (float $meters, string $expectedString) {
    $elevation = new Elevation($meters);

    expect((string) $elevation)->toBe($expectedString);
})->with([
    'positive value' => [1500.5, '1500.5'],
    'zero value' => [0.0, '0'],
    'negative value' => [-500.25, '-500.25'],
    'integer value' => [1000.0, '1000'],
]);

it('returns correct cast class', function () {
    expect(Elevation::castUsing([]))->toBe(\App\Casts\ElevationCast::class);
});

it('resolves displayable value correctly', function (float $meters, float $expectedValue) {
    $elevation = new Elevation($meters);

    expect($elevation->resolveDisplayableValue())->toBe($expectedValue);
})->with([
    'zero elevation' => [0.0, 0.0],
    'one meter' => [1.0, 3.28],
    'ten meters' => [10.0, 32.8],
    'one hundred meters' => [100.0, 328.0],
    'negative elevation' => [-100.0, -328.0],
]);

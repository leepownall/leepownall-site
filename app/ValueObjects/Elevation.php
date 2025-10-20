<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Casts\ElevationCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\DeferringDisplayableValue;

final class Elevation implements Castable, DeferringDisplayableValue
{
    public float $value = 0.0;

    public function __construct(?float $value = null)
    {
        if ($value !== null) {
            $this->value = $value;
        }
    }

    public function isBlank(): bool
    {
        return $this->value === 0.0;
    }

    public function isNotBlank(): bool
    {
        return $this->isBlank() === false;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function asFeet(int $precision = 2): float
    {
        return round(num: $this->value * 3.28, precision: $precision);
    }

    public function asMeters(int $precision = 2): float
    {
        return round(num: $this->value, precision: $precision);
    }

    public static function castUsing(array $arguments): string
    {
        return ElevationCast::class;
    }

    public function resolveDisplayableValue(): float
    {
        return $this->asFeet();
    }
}

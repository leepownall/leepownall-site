<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Casts\DistanceCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\DeferringDisplayableValue;

final class Distance implements Castable, DeferringDisplayableValue
{
    public float $value = 0.0;

    public function __construct(?float $value = null)
    {
        if ($value !== null) {
            $this->value = $value;
        }
    }

    public function asMiles(): float
    {
        return round(num: $this->value * 0.000621, precision: 2);
    }

    public function asKilometers(): float
    {
        return round(num: $this->value / 1000, precision: 2);
    }

    public static function castUsing(array $arguments): string
    {
        return DistanceCast::class;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function resolveDisplayableValue(): float|string
    {
        if ($this->value === 0.0) {
            return '-';
        }

        return $this->asKilometers();
    }
}

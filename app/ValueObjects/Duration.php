<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Casts\DurationCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\DeferringDisplayableValue;

final class Duration implements Castable, DeferringDisplayableValue
{
    public int $value = 0;

    public function __construct(?int $value = null)
    {
        if ($value !== null) {
            $this->value = $value;
        }
    }

    public function asReadable(): string
    {
        $hours = intdiv($this->value, 3600);
        $minutes = intdiv($this->value % 3600, 60);
        $remainingSeconds = $this->value % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    public function resolveDisplayableValue(): float|string
    {
        if ($this->value === 0) {
            return '-';
        }

        return $this->asReadable();
    }

    public static function castUsing(array $arguments)
    {
        return DurationCast::class;
    }
}

<?php

namespace App\Training;

final class RunClassifier
{
    public static function effort(string $name): ?RunEffort
    {
        if (self::contains($name, ['interval', 'intervals', 'fartlek', 'repetition', 'repetitions', 'speed'])) {
            return RunEffort::IntervalPace;
        }

        if (self::contains($name, ['tempo', 'threshold'])) {
            return RunEffort::Tempo;
        }

        if (self::contains($name, ['marathon pace', 'marathon-pace'])) {
            return RunEffort::MarathonPace;
        }

        if (self::contains($name, ['easy', 'recovery', 'long', 'shakeout'])) {
            return RunEffort::Easy;
        }

        return null;
    }

    /**
     * @param  list<string>  $needles
     */
    private static function contains(string $name, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (preg_match('/\b'.preg_quote($needle, '/').'\b/i', $name) === 1) {
                return true;
            }
        }

        return false;
    }
}

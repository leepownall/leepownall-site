<?php

namespace App\Training;

final class RunDuration
{
    /**
     * @param  list<string>  $lines
     * @param  array<string, int>  $secondsPerKilometre
     */
    public static function minutes(string $name, array $lines, array $secondsPerKilometre): ?int
    {
        $estimate = self::estimate($name, $lines, $secondsPerKilometre);

        if ($estimate === null) {
            return null;
        }

        return (int) round($estimate['seconds'] / 60);
    }

    /**
     * @param  list<string>  $lines
     * @param  array<string, int>  $secondsPerKilometre
     * @return array{seconds: int, metres: int, fixedSeconds: int, efforts: list<array{effort: string, metres: int, pace: int}>}|null
     */
    public static function estimate(string $name, array $lines, array $secondsPerKilometre): ?array
    {
        $seconds = 0;
        $metres = 0;
        $fixedSeconds = 0;
        /** @var array<string, array{effort: string, metres: int, pace: int}> $efforts */
        $efforts = [];
        $summaryMetres = null;
        $hasSegments = false;
        $missingPace = false;

        foreach ($lines as $line) {
            if (preg_match('/strides$/i', $line) === 1) {
                continue;
            }

            if (preg_match('/^(\d+) × (\d+) ?(minutes|m) (.+)$/u', $line, $match) === 1) {
                $count = (int) $match[1];
                $value = (int) $match[2];

                if ($match[3] === 'minutes') {
                    $fixedSeconds += $count * $value * 60;
                    $seconds += $count * $value * 60;
                    $hasSegments = true;

                    continue;
                }

                $pace = self::pace($match[4], $secondsPerKilometre);

                if ($pace === null) {
                    $missingPace = true;

                    continue;
                }

                $segmentMetres = $count * $value;
                $metres += $segmentMetres;
                $seconds += (int) round($count * ($value / 1000) * $pace);
                self::addEffort($efforts, strtolower($match[4]), $segmentMetres, $pace);
                $hasSegments = true;

                continue;
            }

            if (preg_match('/^(\d+) (km|minutes)(?: (.+))?$/', $line, $match) !== 1) {
                continue;
            }

            $value = (int) $match[1];

            if (! isset($match[3]) && $match[2] === 'km') {
                $summaryMetres = $value * 1000;

                continue;
            }

            if ($match[2] === 'minutes') {
                $fixedSeconds += $value * 60;
                $seconds += $value * 60;
                $hasSegments = true;

                continue;
            }

            $pace = self::pace($match[3], $secondsPerKilometre);

            if ($pace === null) {
                $missingPace = true;

                continue;
            }

            $metres += $value * 1000;
            $seconds += $value * $pace;
            self::addEffort($efforts, strtolower($match[3]), $value * 1000, $pace);
            $hasSegments = true;
        }

        if (! $hasSegments && $summaryMetres !== null) {
            $effort = RunClassifier::effort($name) ?? RunEffort::Easy;
            $pace = $secondsPerKilometre[$effort->value] ?? null;

            if ($pace === null) {
                return null;
            }

            $metres = $summaryMetres;
            $seconds = (int) round(($summaryMetres / 1000) * $pace);
            self::addEffort($efforts, $effort->value, $summaryMetres, $pace);
            $hasSegments = true;
            $missingPace = false;
        }

        if (! $hasSegments || $missingPace || $seconds <= 0) {
            return null;
        }

        return [
            'seconds' => $seconds,
            'metres' => $metres,
            'fixedSeconds' => $fixedSeconds,
            'efforts' => array_values($efforts),
        ];
    }

    /**
     * @param  array<string, array{effort: string, metres: int, pace: int}>  $efforts
     */
    private static function addEffort(array &$efforts, string $effort, int $metres, int $pace): void
    {
        if (! isset($efforts[$effort])) {
            $efforts[$effort] = [
                'effort' => $effort,
                'metres' => 0,
                'pace' => $pace,
            ];
        }

        $efforts[$effort]['metres'] += $metres;
    }

    /**
     * @param  array<string, int>  $secondsPerKilometre
     */
    private static function pace(string $effort, array $secondsPerKilometre): ?int
    {
        $effort = strtolower($effort);

        if ($effort === 'walk') {
            return null;
        }

        return $secondsPerKilometre[$effort]
            ?? $secondsPerKilometre[RunEffort::Easy->value]
            ?? null;
    }
}

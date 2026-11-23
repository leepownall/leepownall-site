<?php

namespace App\Training;

use App\Models\Activity;
use App\Models\RunPace;

final class RunPaces
{
    public static function recompute(): void
    {
        /** @var array<string, list<int>> $grouped */
        $grouped = [];
        $since = now()->subMonths((int) config('training.pace_months'))->startOfDay();

        Activity::query()
            ->where('started_at', '>=', $since)
            ->orderBy('id')
            ->each(function (Activity $activity) use (&$grouped): void {
                if ($activity->distance_metres <= 0) {
                    return;
                }

                $pace = (int) round($activity->moving_time_seconds / ($activity->distance_metres / 1000));
                $effort = RunClassifier::effort($activity->name) ?? RunEffort::Easy;
                $grouped[$effort->value][] = $pace;
            });

        $averages = self::averages($grouped);
        $targets = config('training.paces', []);

        foreach (RunEffort::cases() as $effort) {
            $seconds = $averages[$effort->value] ?? $targets[$effort->value] ?? null;

            if (! is_numeric($seconds)) {
                RunPace::query()->where('effort', $effort->value)->delete();

                continue;
            }

            RunPace::query()->updateOrCreate(
                ['effort' => $effort->value],
                ['seconds_per_kilometre' => (int) $seconds],
            );
        }

        MarathonPlan::sync();
    }

    /**
     * @param  array<string, list<int>>  $grouped
     * @return array<string, int>
     */
    private static function averages(array $grouped): array
    {
        $averages = [];

        foreach ($grouped as $effort => $paces) {
            $averages[$effort] = (int) round(array_sum($paces) / count($paces));
        }

        return $averages;
    }
}

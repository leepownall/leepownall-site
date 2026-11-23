<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Training\RunPaces;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class ImportStravaActivities implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(public string $path) {}

    public function handle(): void
    {
        $handle = fopen($this->path, 'r');

        if ($handle === false) {
            return;
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            return;
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]) ?? (string) $headers[0];
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            $row = [];

            foreach ($headers as $index => $header) {
                $row[strtolower(trim((string) $header))] = $data[$index] ?? null;
            }

            $activity = $this->activity($row);

            if ($activity === null) {
                continue;
            }

            $rows[] = $activity;

            if (count($rows) === 200) {
                $this->store($rows);
                $rows = [];
            }
        }

        fclose($handle);

        if ($rows !== []) {
            $this->store($rows);
        }

        RunPaces::recompute();
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array{strava_id: int, name: string, started_at: string, distance_metres: int, moving_time_seconds: int, created_at: string, updated_at: string}|null
     */
    private function activity(array $row): ?array
    {
        $distance = (int) round((float) ($row['distance'] ?? 0));
        $movingTime = (int) round((float) ($row['moving time'] ?? 0));
        $stravaId = (int) ($row['activity id'] ?? 0);
        $startedAt = trim((string) ($row['activity date'] ?? ''));

        if (! Activity::isKeptRun((string) ($row['activity type'] ?? ''), $distance, $movingTime) || $stravaId === 0 || $startedAt === '') {
            return null;
        }

        try {
            $started = CarbonImmutable::parse($startedAt);
        } catch (\Exception) {
            return null;
        }

        $now = now()->toDateTimeString();

        return [
            'strava_id' => $stravaId,
            'name' => Str::limit(trim((string) ($row['activity name'] ?? 'Run')), 255, ''),
            'started_at' => $started->toDateTimeString(),
            'distance_metres' => $distance,
            'moving_time_seconds' => $movingTime,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param  list<array{strava_id: int, name: string, started_at: string, distance_metres: int, moving_time_seconds: int, created_at: string, updated_at: string}>  $rows
     */
    private function store(array $rows): void
    {
        Activity::query()->upsert(
            $rows,
            ['strava_id'],
            ['name', 'started_at', 'distance_metres', 'moving_time_seconds', 'updated_at'],
        );
    }
}

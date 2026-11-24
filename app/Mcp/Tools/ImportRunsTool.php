<?php

namespace App\Mcp\Tools;

use App\Models\Activity;
use App\Training\RunPaces;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('import-runs')]
#[Description('Saves activities read from the Strava MCP server so the training page can compare them with the plan. Send recent activities as Strava returns them; runs are kept and other sports are skipped. Sending an activity again updates it. List removed Strava activity IDs in removed_strava_ids to delete them.')]
#[IsIdempotent]
class ImportRunsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'activities' => ['present', 'array'],
            'activities.*.strava_id' => ['required', 'integer', 'min:1'],
            'activities.*.name' => ['required', 'string'],
            'activities.*.sport_type' => ['required', 'string'],
            'activities.*.start_date' => ['required', 'date'],
            'activities.*.distance_metres' => ['required', 'numeric', 'min:0'],
            'activities.*.moving_time_seconds' => ['required', 'integer', 'min:0'],
            'removed_strava_ids' => ['array'],
            'removed_strava_ids.*' => ['integer'],
        ]);

        $now = now()->toDateTimeString();
        $runs = collect($validated['activities'])
            ->map(fn (array $activity): array => [
                'strava_id' => (int) $activity['strava_id'],
                'name' => Str::limit(trim($activity['name']), 255, ''),
                'sport_type' => $activity['sport_type'],
                'started_at' => CarbonImmutable::parse($activity['start_date'])->utc()->toDateTimeString(),
                'distance_metres' => (int) round((float) $activity['distance_metres']),
                'moving_time_seconds' => (int) $activity['moving_time_seconds'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->filter(fn (array $run): bool => Activity::isKeptRun($run['sport_type'], $run['distance_metres'], $run['moving_time_seconds']))
            ->map(fn (array $run): array => collect($run)->except('sport_type')->all())
            ->values();

        if ($runs->isNotEmpty()) {
            Activity::query()->upsert(
                $runs->all(),
                ['strava_id'],
                ['name', 'started_at', 'distance_metres', 'moving_time_seconds', 'updated_at'],
            );
        }

        $removed = Activity::query()->whereIn('strava_id', $validated['removed_strava_ids'] ?? [])->delete();

        RunPaces::recompute();

        return Response::text(sprintf(
            'Saved %d runs, skipped %d other activities, removed %d.',
            $runs->count(),
            count($validated['activities']) - $runs->count(),
            $removed,
        ));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'activities' => $schema->array()
                ->items($schema->object([
                    'strava_id' => $schema->integer()->description('The Strava activity ID.')->required(),
                    'name' => $schema->string()->description('The activity name on Strava.')->required(),
                    'sport_type' => $schema->string()->description('Strava sport_type, such as Run, TrailRun, or Ride.')->required(),
                    'start_date' => $schema->string()->description('Start time in ISO 8601 with its offset, such as Strava start_date 2026-11-22T07:30:00Z.')->required(),
                    'distance_metres' => $schema->number()->description('Distance in metres.')->required(),
                    'moving_time_seconds' => $schema->integer()->description('Moving time in seconds.')->required(),
                ]))
                ->description('Activities to save, such as the last two weeks from Strava.')
                ->required(),
            'removed_strava_ids' => $schema->array()
                ->items($schema->integer())
                ->description('Strava activity IDs deleted on Strava, to remove from the site.'),
        ];
    }
}

<?php

namespace App\Mcp\Tools;

use App\Models\StravaRoute;
use App\Training\RouteTime;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('import-routes')]
#[Description('Replaces the running routes on the site with routes read from the Strava MCP server. Send every running route, because routes left out are removed. A route is nighttime (lit) when its name or description says "night" or "lit"; every other route is daytime.')]
#[IsDestructive]
#[IsIdempotent]
class ImportRoutesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'routes' => ['present', 'array'],
            'routes.*.strava_id' => ['required', 'string', 'max:32', 'distinct'],
            'routes.*.name' => ['required', 'string'],
            'routes.*.description' => ['nullable', 'string'],
            'routes.*.distance_metres' => ['required', 'numeric', 'min:0'],
            'routes.*.elevation_gain_metres' => ['nullable', 'numeric', 'min:0'],
        ]);

        StravaRoute::query()->getConnection()->transaction(function () use ($validated): void {
            StravaRoute::query()->delete();

            foreach ($validated['routes'] as $route) {
                $name = Str::limit(trim($route['name']), 255, '');
                $description = trim((string) ($route['description'] ?? '')) ?: null;

                StravaRoute::query()->create([
                    'strava_id' => $route['strava_id'],
                    'name' => $name,
                    'description' => $description,
                    'distance_metres' => (int) round((float) $route['distance_metres']),
                    'elevation_gain_metres' => (int) round((float) ($route['elevation_gain_metres'] ?? 0)),
                    'time' => RouteTime::fromStrava($name, $description),
                ]);
            }
        });

        return Response::text(sprintf(
            'Saved %d routes, %d of them for night running.',
            StravaRoute::query()->count(),
            StravaRoute::query()->where('time', RouteTime::Nighttime)->count(),
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
            'routes' => $schema->array()
                ->items($schema->object([
                    'strava_id' => $schema->string()->description('The Strava route ID as a string (id_str), since route IDs are too large for some clients as numbers.')->required(),
                    'name' => $schema->string()->description('The route name on Strava.')->required(),
                    'description' => $schema->string()->description('The route description on Strava, if any.'),
                    'distance_metres' => $schema->number()->description('Route distance in metres.')->required(),
                    'elevation_gain_metres' => $schema->number()->description('Climb in metres.'),
                ]))
                ->description('Every running route. Leave out ride routes.')
                ->required(),
        ];
    }
}

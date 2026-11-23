<?php

namespace App\Mcp\Tools;

use App\Models\StravaRoute;
use App\Training\RouteTime;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('list-routes')]
#[Description('Lists the running routes saved on Strava, shortest first, with distance, climb, and whether each is a daytime or nighttime route. Nighttime routes are lit and safe in the dark; a route is nighttime when its Strava name or description says "night" or "lit". Routes are copied from Strava when the owner presses Sync on the routes page, so a route created since then is missing until they do. Use it to pick a route for a session or to find distances that need more nighttime routes.')]
class ListRoutesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'time' => ['nullable', Rule::enum(RouteTime::class)],
            'min_km' => ['nullable', 'numeric', 'min:0'],
            'max_km' => ['nullable', 'numeric', 'min:0'],
        ]);

        $routes = StravaRoute::query()
            ->when($validated['time'] ?? null, fn (Builder $query, string $time): Builder => $query->where('time', $time))
            ->when(isset($validated['min_km']), fn (Builder $query): Builder => $query->where('distance_metres', '>=', $validated['min_km'] * 1000))
            ->when(isset($validated['max_km']), fn (Builder $query): Builder => $query->where('distance_metres', '<=', $validated['max_km'] * 1000))
            ->orderBy('distance_metres')
            ->get();

        return Response::json([
            'routes' => $routes->map(fn (StravaRoute $route): array => [
                'name' => $route->name,
                'km' => $route->kilometres(),
                'climb_metres' => $route->elevation_gain_metres,
                'time' => $route->time->value,
                'url' => $route->url(),
            ])->all(),
            'nighttime_count' => $routes->where('time', RouteTime::Nighttime)->count(),
            'daytime_count' => $routes->where('time', RouteTime::Daytime)->count(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'time' => $schema->string()
                ->enum(array_column(RouteTime::cases(), 'value'))
                ->description('Only daytime routes, or only nighttime (lit) routes. Leave out for both.'),
            'min_km' => $schema->number()
                ->description('Shortest route to include, in kilometres.'),
            'max_km' => $schema->number()
                ->description('Longest route to include, in kilometres.'),
        ];
    }
}

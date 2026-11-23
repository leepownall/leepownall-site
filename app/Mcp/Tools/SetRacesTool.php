<?php

namespace App\Mcp\Tools;

use App\Models\Race;
use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('set-races')]
#[Description('Replaces the races shown on the training page. Send every race, past and future, because races left out are removed. Mark exactly one race as the target: the race the plan builds towards, which the site counts down to. Other races, such as tune-up races, show on their day.')]
#[IsDestructive]
#[IsIdempotent]
class SetRacesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $exactlyOneTarget = function (string $attribute, mixed $races, Closure $fail): void {
            $targets = collect($races)->filter(fn (mixed $race): bool => (bool) ($race['target'] ?? false));

            if (collect($races)->isNotEmpty() && $targets->count() !== 1) {
                $fail('Mark exactly one race as the target.');
            }
        };

        $validated = $request->validate([
            'races' => ['present', 'array', $exactlyOneTarget],
            'races.*.name' => ['required', 'string', 'max:255'],
            'races.*.date' => ['required', 'date_format:Y-m-d'],
            'races.*.distance_km' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'races.*.target' => ['boolean'],
        ]);

        Race::query()->getConnection()->transaction(function () use ($validated): void {
            Race::query()->delete();

            foreach ($validated['races'] as $race) {
                Race::query()->create([
                    'name' => $race['name'],
                    'date' => $race['date'],
                    'distance_metres' => (int) round($race['distance_km'] * 1000),
                    'is_target' => (bool) ($race['target'] ?? false),
                ]);
            }
        });

        $target = Race::query()->where('is_target', true)->first();

        return Response::text(sprintf(
            'Saved %d races.%s',
            count($validated['races']),
            $target === null ? '' : " The target is {$target->name} on {$target->date->toDateString()}.",
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
            'races' => $schema->array()
                ->items($schema->object([
                    'name' => $schema->string()->description('The race name, such as Manchester Marathon.')->required(),
                    'date' => $schema->string()->description('Race day, as YYYY-MM-DD.')->required(),
                    'distance_km' => $schema->number()->description('Race distance in kilometres, such as 42.195 or 16.1 for ten miles.')->required(),
                    'target' => $schema->boolean()->description('true for the one race the plan builds towards.'),
                ]))
                ->description('Every race. An empty list removes them all.')
                ->required(),
        ];
    }
}

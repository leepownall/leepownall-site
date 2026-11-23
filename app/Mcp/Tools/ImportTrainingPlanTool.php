<?php

namespace App\Mcp\Tools;

use App\Training\MarathonPlan;
use App\Training\WeekKind;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('import-training-plan')]
#[Description('Replaces the whole training plan shown on the site. Send every week of the plan, past and future, because weeks that are left out are removed. Each week starts on a Monday and has seven days, Monday first. Prep weeks are the build-up before the plan; plan weeks are the plan itself. Weeks are numbered by date within their kind.')]
#[IsDestructive]
#[IsIdempotent]
class ImportTrainingPlanTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'weeks' => ['required', 'array', 'min:1'],
            'weeks.*.starts_on' => ['required', 'date_format:Y-m-d', 'distinct', function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && CarbonImmutable::hasFormat($value, 'Y-m-d') && ! CarbonImmutable::parse($value)->isMonday()) {
                    $fail("{$value} is not a Monday. Each week must start on a Monday.");
                }
            }],
            'weeks.*.kind' => ['required', Rule::enum(WeekKind::class)],
            'weeks.*.phase' => ['required', 'string', 'max:255'],
            'weeks.*.days' => ['required', 'array', 'size:7'],
            'weeks.*.days.*.name' => ['nullable', 'string', 'max:255'],
            'weeks.*.days.*.lines' => ['nullable', 'array'],
            'weeks.*.days.*.lines.*' => ['string', 'max:255'],
            'weeks.*.days.*.rest' => ['boolean'],
            'weeks.*.days.*.strength' => ['boolean'],
        ], [
            'weeks.*.days.size' => 'Each week needs exactly seven days, Monday first.',
        ]);

        MarathonPlan::replace($validated['weeks']);

        return Response::text(sprintf(
            'Imported %d build-up weeks and %d plan weeks.',
            MarathonPlan::prepLength(),
            MarathonPlan::planLength(),
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
            'weeks' => $schema->array()
                ->items($schema->object([
                    'starts_on' => $schema->string()
                        ->description('The Monday the week starts, as YYYY-MM-DD.')
                        ->required(),
                    'kind' => $schema->string()
                        ->enum(array_column(WeekKind::cases(), 'value'))
                        ->description('prep for a build-up week before the plan starts, plan for a week of the plan.')
                        ->required(),
                    'phase' => $schema->string()
                        ->description('The phase the week belongs to, such as Build-up, Build, Peak, Taper, or Race.')
                        ->required(),
                    'days' => $schema->array()
                        ->items($schema->object([
                            'name' => $schema->string()
                                ->description('The run, such as Easy run, Tempo run, Long run, or a race name. Leave out on a day without a run.'),
                            'lines' => $schema->array()
                                ->items($schema->string())
                                ->description('The run written line by line. Start with the total, such as "16 km", then each part, such as "6 km easy", "5 km marathon pace", "4 × 20s strides", or "10 minutes easy". Efforts are easy, marathon pace, tempo, interval pace, and walk.'),
                            'rest' => $schema->boolean()
                                ->description('true on a rest day.'),
                            'strength' => $schema->boolean()
                                ->description('true when the day includes a 30 minute strength session.'),
                        ]))
                        ->min(7)
                        ->max(7)
                        ->description('Seven days, Monday to Sunday.')
                        ->required(),
                ]))
                ->min(1)
                ->description('Every week of the plan.')
                ->required(),
        ];
    }
}

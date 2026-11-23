<?php

namespace App\Mcp\Tools;

use App\Models\Race;
use App\Training\MarathonPlan;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-training-plan')]
#[Description('Returns the training plan stored on the site: every week with its kind, number, phase, and seven days of sessions, and the races, one of them the target.')]
#[IsReadOnly]
class GetTrainingPlanTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        return Response::json([
            'weeks' => MarathonPlan::export(),
            'races' => Race::query()->orderBy('date')->get()->map(fn (Race $race): array => [
                'name' => $race->name,
                'date' => $race->date->toDateString(),
                'distance_km' => $race->kilometres(),
                'target' => $race->is_target,
            ])->all(),
        ]);
    }
}

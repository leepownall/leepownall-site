<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetTrainingPlanTool;
use App\Mcp\Tools\ImportTrainingPlanTool;
use App\Mcp\Tools\ListRoutesTool;
use App\Mcp\Tools\SetRacesTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Lee Pownall Training')]
#[Version('1.0.0')]
#[Instructions('Manages the marathon training plan shown at /training on leepownall.com. Use get-training-plan to read the stored plan and import-training-plan to replace it after the plan changes. Use set-races to keep the races in line with the plan, with exactly one target race. Use list-routes to choose a Strava route for a session; most runs are early in the morning in the dark, so prefer nighttime routes unless told otherwise. Runs recorded on Strava are imported by the site itself; use the Strava MCP server to read them.')]
class TrainingServer extends Server
{
    protected array $tools = [
        ImportTrainingPlanTool::class,
        GetTrainingPlanTool::class,
        ListRoutesTool::class,
        SetRacesTool::class,
    ];
}

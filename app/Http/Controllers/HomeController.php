<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\BodyBattery;
use App\Models\Sleep;
use App\Models\Step;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use function number_format;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $steps = Step::query()->latest()->first()?->amount ?? 0;
        $sleep = Sleep::query()->latest()->first()?->amount ?? 0;
        $bodyBattery = BodyBattery::query()->latest()->first()?->amount ?? 0;

        return Inertia::render('Home', [
            'steps' => number_format($steps),
            'sleep' => number_format($sleep),
            'bodyBattery' => number_format($bodyBattery),
            'activity' => ActivityResource::make(
                Activity::query()
                    ->select([
                        'activity_id',
                        'name',
                        'type',
                        'distance',
                        'moving_time',
                        'elapsed_time',
                        'total_elevation_gain',
                        'started_at',
                    ])
                    ->latest('started_at')
                    ->first(),
            ),
        ]);
    }
}
